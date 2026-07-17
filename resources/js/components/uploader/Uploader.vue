<script setup lang="ts">
import { create, registerPlugin } from 'filepond';
import type {
    FilePond as FilePondInstance,
    FilePondFile,
    FilePondInitialFile,
} from 'filepond';
import FilePondPluginFilePoster from 'filepond-plugin-file-poster';
import 'filepond-plugin-file-poster/dist/filepond-plugin-file-poster.css';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';
import 'filepond/dist/filepond.css';
import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
    shallowRef,
    watch,
} from 'vue';

import { useTrans } from '@/composables/useTrans';

export type UploaderExistingFile = {
    id: string | number;
    source: string;
    name: string;
    size: number;
    type?: string | null;
    poster?: string | null;
};

export type UploaderMessages = {
    invalidType: string;
    tooLarge: string;
    uploadFailed: string;
    removeFailed: string;
};

type Props = {
    id?: string;
    uploadUrl: string;
    deleteUrlResolver: (temporaryUploadId: string) => string;
    modelValue?: string[];
    removed?: Array<string | number>;
    existingFiles?: UploaderExistingFile[];
    multiple?: boolean;
    acceptedFileTypes?: string[];
    maxFileSize?: number | null;
    uploadFieldName?: string;
    labelIdle?: string;
    cleanupOnUnmount?: boolean;
    previewSize?: 'default' | 'compact';
    messages?: Partial<UploaderMessages>;
};

type Emits = {
    'update:modelValue': [value: string[]];
    'update:removed': [value: Array<string | number>];
    updateTemporaryUploadIds: [value: string[]];
    updateRemovedExistingMediaIds: [value: Array<string | number>];
    'upload-error': [message: string];
};

type TemporaryUploadResponse = {
    id: string;
    name: string;
    size: number;
    type: string | null;
};

type FilePondPluginContext = {
    utils: {
        Type: {
            ARRAY: 'array';
        };
    };
};

const props = withDefaults(defineProps<Props>(), {
    id: undefined,
    modelValue: () => [],
    removed: () => [],
    existingFiles: () => [],
    multiple: false,
    acceptedFileTypes: () => [],
    maxFileSize: null,
    uploadFieldName: 'file',
    labelIdle: undefined,
    cleanupOnUnmount: true,
    previewSize: 'default',
    messages: () => ({}),
});

const emit = defineEmits<Emits>();

const { trans } = useTrans();

const FilePondAcceptedFileTypesPlugin = ({ utils }: FilePondPluginContext) => ({
    options: {
        acceptedFileTypes: [[] as string[], utils.Type.ARRAY],
    },
});

let pluginsRegistered = false;

const defaultMessages = computed<UploaderMessages>(() => ({
    invalidType: trans('general.uploader.invalid_type'),
    tooLarge: trans('general.uploader.too_large'),
    uploadFailed: trans('general.uploader.upload_failed'),
    removeFailed: trans('general.uploader.remove_failed'),
}));

const resolvedLabelIdle = computed(
    () => props.labelIdle ?? trans('general.uploader.idle'),
);

const pondInput = ref<HTMLInputElement | null>(null);
const pond = shallowRef<FilePondInstance | null>(null);
const temporaryUploadIds = ref<string[]>([...props.modelValue]);
const removedExistingIds = ref<Array<string | number>>([...props.removed]);

watch(
    () => props.modelValue,
    (value) => {
        temporaryUploadIds.value = [...value];
    },
);

watch(
    () => props.removed,
    (value) => {
        removedExistingIds.value = [...value];
    },
);

const csrfToken = computed(() => {
    if (typeof document === 'undefined') {
        return '';
    }

    return (
        document
            .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
});

const messages = computed<UploaderMessages>(() => ({
    ...defaultMessages.value,
    ...props.messages,
}));

const pondFiles = computed<FilePondInitialFile[]>(() =>
    props.existingFiles
        .filter((file) => !removedExistingIds.value.includes(file.id))
        .map((file) => ({
            source: file.source,
            options: {
                type: 'local',
                file: {
                    name: file.name,
                    size: file.size,
                    type: file.type ?? undefined,
                },
                metadata: {
                    existingFileId: file.id,
                    poster: file.poster ?? file.source,
                },
            },
        })),
);

watch(pondFiles, (files) => {
    pond.value?.setOptions({ files });
});

const imagePreviewHeight = computed(() =>
    props.previewSize === 'compact' ? 80 : 140,
);

const maxFiles = computed(() => (props.multiple ? undefined : 1));

const server = computed(() => ({
    process: (
        _fieldName: string,
        file: File,
        _metadata: Record<string, unknown>,
        load: (serverId: string) => void,
        error: (message: string) => void,
        progress: (computable: boolean, loaded: number, total: number) => void,
        abort: () => void,
    ) => {
        if (!isAcceptedFileType(file)) {
            emit('upload-error', messages.value.invalidType);
            error(messages.value.invalidType);

            return { abort };
        }

        if (props.maxFileSize !== null && file.size > props.maxFileSize) {
            emit('upload-error', messages.value.tooLarge);
            error(messages.value.tooLarge);

            return { abort };
        }

        const formData = new FormData();
        formData.append(props.uploadFieldName, file, file.name);

        const request = new XMLHttpRequest();
        request.open('POST', props.uploadUrl);
        request.withCredentials = true;
        request.setRequestHeader('Accept', 'application/json');
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        if (csrfToken.value !== '') {
            request.setRequestHeader('X-CSRF-TOKEN', csrfToken.value);
        }

        request.upload.onprogress = (event) => {
            progress(event.lengthComputable, event.loaded, event.total);
        };

        request.onload = () => {
            if (request.status < 200 || request.status >= 300) {
                const message = parseErrorMessage(request.responseText);

                emit('upload-error', message);
                error(message);

                return;
            }

            const response = JSON.parse(
                request.responseText,
            ) as TemporaryUploadResponse;

            const nextIds = props.multiple
                ? [...temporaryUploadIds.value, response.id]
                : [response.id];

            if (!props.multiple) {
                temporaryUploadIds.value
                    .filter(
                        (temporaryUploadId) =>
                            temporaryUploadId !== response.id,
                    )
                    .forEach((temporaryUploadId) => {
                        void deleteTemporaryUpload(temporaryUploadId);
                    });
            }

            syncTemporaryUploadIds(nextIds);
            emit('upload-error', '');
            load(response.id);
        };

        request.onerror = () => {
            emit('upload-error', messages.value.uploadFailed);
            error(messages.value.uploadFailed);
        };

        emit('upload-error', '');
        request.send(formData);

        return {
            abort: () => {
                request.abort();
                abort();
            },
        };
    },
    revert: (
        temporaryUploadId: string,
        load: () => void,
        error: (message: string) => void,
    ) => {
        deleteTemporaryUpload(temporaryUploadId)
            .then(load)
            .catch(() => {
                error(messages.value.removeFailed);
            });
    },
}));

function isAcceptedFileType(file: File): boolean {
    if (props.acceptedFileTypes.length === 0) {
        return true;
    }

    return props.acceptedFileTypes.some((acceptedFileType) => {
        if (acceptedFileType.endsWith('/*')) {
            return file.type.startsWith(acceptedFileType.slice(0, -1));
        }

        return file.type === acceptedFileType;
    });
}

function parseErrorMessage(responseText: string): string {
    try {
        const response = JSON.parse(responseText) as {
            message?: string;
            errors?: Record<string, string[]>;
        };

        return (
            response.errors?.[props.uploadFieldName]?.[0] ??
            response.message ??
            messages.value.uploadFailed
        );
    } catch {
        return messages.value.uploadFailed;
    }
}

async function deleteTemporaryUpload(
    temporaryUploadId: string,
    keepalive: boolean = false,
): Promise<void> {
    if (!temporaryUploadIds.value.includes(temporaryUploadId)) {
        return;
    }

    const response = await fetch(props.deleteUrlResolver(temporaryUploadId), {
        method: 'DELETE',
        credentials: 'same-origin',
        keepalive,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken.value !== ''
                ? {
                      'X-CSRF-TOKEN': csrfToken.value,
                  }
                : {}),
        },
    });

    if (!response.ok) {
        throw new Error(messages.value.removeFailed);
    }

    syncTemporaryUploadIds(
        temporaryUploadIds.value.filter((id) => id !== temporaryUploadId),
    );
}

function handleRemoveFile(
    _error: unknown,
    file: FilePondFile | undefined,
): void {
    if (!file) {
        return;
    }

    const existingFileId = file.getMetadata('existingFileId');

    if (existingFileId === undefined || existingFileId === null) {
        return;
    }

    if (!removedExistingIds.value.includes(existingFileId)) {
        syncRemovedExistingIds([...removedExistingIds.value, existingFileId]);
    }
}

function cleanupTemporaryUploads(keepalive: boolean = false): void {
    temporaryUploadIds.value.forEach((temporaryUploadId) => {
        void deleteTemporaryUpload(temporaryUploadId, keepalive);
    });
}

function handlePageHide(): void {
    cleanupTemporaryUploads(true);
}

function syncTemporaryUploadIds(value: string[]): void {
    temporaryUploadIds.value = value;
    emit('update:modelValue', value);
    emit('updateTemporaryUploadIds', value);
}

function syncRemovedExistingIds(value: Array<string | number>): void {
    removedExistingIds.value = value;
    emit('update:removed', value);
    emit('updateRemovedExistingMediaIds', value);
}

onMounted(() => {
    if (!pluginsRegistered) {
        registerPlugin(
            FilePondPluginImagePreview,
            FilePondPluginFilePoster,
            FilePondAcceptedFileTypesPlugin,
        );
        pluginsRegistered = true;
    }

    if (pondInput.value !== null) {
        pond.value = create(pondInput.value, {
            files: pondFiles.value,
            server: server.value as never,
            allowMultiple: props.multiple,
            maxFiles: maxFiles.value,
            allowReorder: false,
            allowFilePoster: true,
            filePosterHeight: imagePreviewHeight.value,
            filePosterMaxHeight: imagePreviewHeight.value,
            allowImagePreview: true,
            acceptedFileTypes: props.acceptedFileTypes,
            labelIdle: resolvedLabelIdle.value,
            credits: false,
            imagePreviewHeight: imagePreviewHeight.value,
            onremovefile: handleRemoveFile,
        } as never);
    }

    window.addEventListener('pagehide', handlePageHide);
});

onBeforeUnmount(() => {
    window.removeEventListener('pagehide', handlePageHide);

    if (props.cleanupOnUnmount) {
        cleanupTemporaryUploads();
    }

    pond.value?.destroy();
    pond.value = null;
});
</script>

<template>
    <div class="grid gap-2">
        <input ref="pondInput" :id="id" :name="uploadFieldName" />
    </div>
</template>

<style scoped>
:deep(.filepond--root) {
    margin-bottom: 0;
}

:deep(.filepond--panel-root) {
    background-color: color-mix(in oklab, var(--muted) 70%, transparent);
    border: 1px solid var(--border);
    border-radius: calc(var(--radius) - 2px);
}

:deep(.filepond--drop-label) {
    color: var(--muted-foreground);
}
</style>
