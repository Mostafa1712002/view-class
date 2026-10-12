# Requirements: rawaniedu.com instance

Second deployment of this codebase, branded «رواني», on the Tarweig VPS (198.244.189.4) next to aafaq/alawal.

- WHEN a visitor opens https://rawaniedu.com THE SYSTEM SHALL serve this app with the brand name «رواني».
- THE SYSTEM SHALL use its own database and share code with ViewClass through the `main` branch.
- WHEN the DNS is changed THE SYSTEM SHALL keep the domain's Hostinger mail records (MX/SPF/DKIM/DMARC) intact.
- WHEN the database is empty THE SYSTEM SHALL install with `migrate` + `db:seed` without errors.
