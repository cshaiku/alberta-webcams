# Public source review

October 31, 2026, owner requested public GitHub visibility and a Reddit Showoff Saturday post.

The repository contains the reconstructed application, public camera metadata, tests and deployment documentation. Original recovered archives and scripts were kept outside Git. Runtime captures, deployment overrides, user preferences and temporary evidence are ignored and are not committed. The full Git history was scanned with Gitleaks. Five generic API key alerts were verified as ordinary SHA256 deployment file hashes; their exact historical fingerprints are recorded in `.gitleaksignore`. No credential finding remained after those reviewed false positives. Included third party licenses remain with their source. Publishing does not grant additional rights to provider camera imagery.
