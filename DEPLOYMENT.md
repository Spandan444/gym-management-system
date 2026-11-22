# Deploy on Vercel with TiDB Cloud

This project uses PHP, so GitHub Pages cannot run it. The Vercel configuration
packages the app in a FrankenPHP container. TiDB Cloud supplies the persistent
MySQL-compatible database.

## 1. Create the TiDB database

1. Create a TiDB Cloud cluster and database named `gym_management`.
2. In the cluster's Connect panel, note the host, username, and password.
   TiDB Cloud Serverless commonly uses port `4000`.
3. Import `sql/gym_management.sql` into the `gym_management` database using
   TiDB's SQL editor or a MySQL-compatible client. If the tool already selected
   the database and rejects the `CREATE DATABASE` or `USE` statements, remove
   those two statements before importing.
4. Permit connections from your Vercel deployment in the TiDB network access
   settings. Prefer a static/controlled Vercel egress address when available;
   avoid opening database access broadly for a production system.

The app creates its `Users` and `App_Sessions` tables automatically on its
first successful request. Set the production admin password before the first
request; member demo accounts use `member123`, so use only sample data unless
you also replace those accounts and credentials.

## 2. Deploy the GitHub repository to Vercel

1. In Vercel, choose **Add New Project** and import
   `Spandan444/gym-management-system`.
2. Keep the project root as `.`. The repository includes `vercel.json`,
   `Dockerfile.vercel`, and `Caddyfile` for the PHP container.
3. Add these environment variables in **Project Settings > Environment
   Variables** for Production (and Preview if needed):

   | Name | Value |
   | --- | --- |
   | `TIDB_HOST` | TiDB connection host |
   | `TIDB_USER` | TiDB connection username |
   | `TIDB_PASSWORD` | TiDB password |
   | `TIDB_DATABASE` | `gym_management` |
   | `TIDB_PORT` | `4000` (use the port shown by TiDB) |
   | `TIDB_SSL` | `true` |
   | `ADMIN_INITIAL_PASSWORD` | A strong private password for the initial `admin` account |

4. Set these values before the first deployment request so the initial admin
   account is created with your private password. This variable is only used
   when the `Users` table is empty.
5. Deploy the project. After adding or changing environment variables, redeploy
   so the new values are applied.
6. Open the deployment URL and test login, navigation, and a small create/edit
   operation. Do not use real member or payment data until access controls and
   backups are in place.

## Local development

Without `TIDB_*` environment variables or `config.local.php`, the app continues
to use the existing XAMPP defaults. For a local TiDB connection, copy
`config.local.php.example` to `config.local.php` and enter your credentials.
That private file is excluded from Git.

## Notes

- PHP sessions are stored in TiDB rather than the Vercel container filesystem,
  so login state survives requests routed to different instances.
- Never commit database passwords or upload `config.local.php` to GitHub.
- Vercel container availability and pricing depend on the Vercel plan.
