# Queued Image Processing

All application image uploads use a two-phase workflow:

1. The original upload is staged synchronously so the uploader can return an opaque staging ID and support cancellation.
2. Saving the parent form promotes the validated original into a Spatie Media Library collection.
3. Every derived image conversion is dispatched to Laravel's queue. Web requests must not run image conversions synchronously.
4. Consumers use a generated conversion when `hasGeneratedConversion()` is true and fall back to the validated original while the conversion is pending or if its job fails.
5. This policy applies to profile avatars and Style Settings assets, including icon, logo, and split-auth background images.
6. Favicon originals may be served directly when their required format should be preserved. Non-image document uploads are not forced through image-conversion jobs.

Rationale: keep request latency and web-server image-processing load bounded without making newly uploaded media unavailable while workers process conversions. Queue workers and failed-job monitoring are operational requirements for deployments that enable uploads.
