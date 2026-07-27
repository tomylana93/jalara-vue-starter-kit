# Temporary Agent Runs

Store task-local packets, handoffs, findings, and final reports under `.ai/runs/<task-id>/`.

Rules:

- runtime files are local and ignored by Git;
- do not store secrets, credentials, tokens, production extracts, or unnecessary personal data;
- do not use this directory as durable architecture documentation;
- do not copy task progress into Serena memory;
- GitHub issues and pull requests remain the durable collaboration record;
- delete or archive local run files after the task is complete.

The committed templates live in `.ai/templates/`.