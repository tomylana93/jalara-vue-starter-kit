<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import type { OTPInputProps } from "vue-input-otp"
import { reactiveOmit } from "@vueuse/core"
import { OTPInput } from "vue-input-otp"
import { cn } from "@/lib/utils"

const props = defineProps<OTPInputProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")
</script>

<template>
  <OTPInput
    v-slot="slotProps"
    v-bind="delegatedProps"
    :container-class="cn('flex items-center gap-2 has-disabled:opacity-50', props.class)"
    data-slot="input-otp"
    class="disabled:cursor-not-allowed"
  >
    <slot v-bind="slotProps" />
  </OTPInput>
</template>
