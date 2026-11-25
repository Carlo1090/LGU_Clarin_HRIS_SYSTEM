# Employee identifier encryption

The schema migration widens `employees.tin` and `employees.gsis_bp_number` to nullable `TEXT`. It leaves existing values intact. The separate conversion command uses Laravel encryption with the environment's `APP_KEY`. Keep that key in the approved secrets store with the backup retention policy. Verify decryption before retiring an old key.

Run cutover only after the app can read encrypted identifiers and all write paths encrypt or reject new plaintext values. Pause writes during conversion and rollback. Keep database statement and bind-parameter logging off during both operations.

1. Back up the database. Restore the backup into an isolated instance, then verify row counts and that the restored application can decrypt encrypted data with the approved key. Keep production backups and keys out of the repository.
2. Apply the widening migration. Run `php artisan employees:encrypt-identifiers` and record its row and value counts. The dry run checks encryption and decryption without modifying rows or printing identifiers.
3. Run `php artisan employees:encrypt-identifiers --apply --backup-verified`. The flag records operator confirmation of the restored backup. Each employee is converted under a row lock. Rerun the same command after an interruption.
4. Repeat the dry run and require zero pending values before reopening writes.

For rollback, dry-run with `--reverse`, verify the backup and key again, then run `php artisan employees:encrypt-identifiers --reverse --apply --backup-verified` in a restricted maintenance window. It restores plaintext, so handle the restored data under the existing restricted access policy. Narrow the columns only after verifying that no encrypted identifiers remain. The schema rollback refuses to narrow columns containing encrypted or oversized values.

Repository inspection found no TIN or GSIS search or uniqueness constraint. There is no lookup hash to backfill.
