# Connecting CyberGuard to Google Workspace

This guide walks you through connecting CyberGuard to your Google Workspace so you can:

- **Search and select groups and OUs** when creating campaigns (directory integration)
- **Resolve group membership** (including nested groups) to send one email per person
- Optionally use **Gmail remediation** (trash confirmed phishing from mailboxes) and **Gmail/Drive** features

You will create a **Google Cloud project**, a **service account** with a JSON key, and give it **domain-wide delegation** in Google Admin Console so it can act on behalf of users in your domain.

---

## Prerequisites

- A **Google Workspace** account (admin access for domain-wide delegation).
- Access to **Google Cloud Console** ([console.cloud.google.com](https://console.cloud.google.com)) and **Google Admin Console** ([admin.google.com](https://admin.google.com)).

---

## Step 1: Create or select a Google Cloud project

1. Go to [Google Cloud Console](https://console.cloud.google.com).
2. In the top bar, click the project dropdown and either:
   - **Create a new project:** Click **New Project**, name it (e.g. `CyberGuard`), choose your organization if prompted, then **Create**.
   - Or **select an existing project** you use for internal tools.
3. Make sure the correct project is selected (name shown in the top bar).

---

## Step 2: Enable the Admin SDK Directory API

1. In Cloud Console, open **APIs & Services** → **Library** (or search “API Library”).
2. Search for **Admin SDK API**.
3. Click **Admin SDK API** and then **Enable**.
4. If you plan to use **Gmail remediation** (trash phishing from mailboxes), also enable **Gmail API** the same way.

---

## Step 3: Create a service account and download the JSON key

1. Go to **APIs & Services** → **Credentials**.
2. Click **Create credentials** → **Service account**.
3. **Service account name:** e.g. `cyberguard-directory`.
4. **Service account ID** will be generated (e.g. `cyberguard-directory@your-project.iam.gserviceaccount.com`). Note this; you’ll use it in Admin Console.
5. Click **Create and continue**. You can skip optional steps (roles, user access) and click **Done**.
6. On the Credentials page, find the service account you just created and click it.
7. Open the **Keys** tab → **Add key** → **Create new key** → **JSON** → **Create**. A JSON file will download.
8. **Store this file securely:**
   - Keep it **outside** your web root (e.g. not inside `public/` or `htdocs/`).
   - Example: `C:\secure\cyberguard\google-service-account.json` (Windows) or `/etc/cyberguard/google-service-account.json` (Linux).
   - Ensure the web server (PHP) can read the file. Note the **full path**; you’ll enter it in CyberGuard.

---

## Step 4: Domain-wide delegation (Google Admin Console)

Domain-wide delegation lets the service account act on behalf of users in your Google Workspace domain (e.g. list groups, list group members, list OUs).

1. Go to [Google Admin Console](https://admin.google.com) and sign in as a **Super Admin** (or an admin with “Manage API client access”).
2. Go to **Security** → **Access and data control** → **API controls**.
3. Click **Manage Domain Wide Delegation** (or search “Domain wide delegation” in Admin Console).
4. Click **Add new**.
5. **Client ID:**  
   - Go back to Cloud Console → **APIs & Services** → **Credentials**.  
   - Click your **service account**.  
   - Copy the **Unique ID** (numeric). Paste it into the Admin Console “Client ID” field.
6. **OAuth Scopes:** Enter this **exact** comma-separated list (no spaces):

   ```
   https://www.googleapis.com/auth/admin.directory.user.readonly,https://www.googleapis.com/auth/admin.directory.group.readonly,https://www.googleapis.com/auth/admin.directory.group.member.readonly,https://www.googleapis.com/auth/admin.directory.orgunit.readonly
   ```

   These allow CyberGuard to:
   - List users (for OUs).
   - List groups and list group members (including nested groups).
   - List organizational units.

   If you also use **Gmail remediation**, add:

   ```
   ,https://www.googleapis.com/auth/gmail.modify
   ```

   So the full list would be:

   ```
   https://www.googleapis.com/auth/admin.directory.user.readonly,https://www.googleapis.com/auth/admin.directory.group.readonly,https://www.googleapis.com/auth/admin.directory.group.member.readonly,https://www.googleapis.com/auth/admin.directory.orgunit.readonly,https://www.googleapis.com/auth/gmail.modify
   ```

7. Click **Authorize**.

---

## Step 5: Configure CyberGuard

### Option A: Per-tenant (recommended for multiple tenants)

1. Log in to CyberGuard as an admin.
2. Go to **Settings**.
3. Select the **tenant** you want to connect (e.g. your school or org).
4. Click **Edit tenant**.
5. Scroll to **Directory integration (Google Workspace)**.
6. Fill in:
   - **Upload service account JSON:** Click **Choose File** and select the JSON key from Step 3. The file is stored securely on the server for this tenant only (not web-accessible). To replace it, upload a new file.
   - **Or enter server path (optional):** Only if the JSON is at a fixed path on the server; leave blank when using the upload.
   - **Admin email (impersonate for API):** A **Google Workspace admin user** in your domain (e.g. `admin@yourdomain.com`). The service account will act “as” this user when calling the Directory API. This user must have access to the groups and OUs you want to list.
   - Check **Enable directory sync**.
7. Set **Allowed domains** (comma-separated) to the domains that may receive simulation emails (e.g. `yourdomain.com`).
8. Click **Save tenant**.

### Option B: Global defaults via .env

If you prefer not to set the path per tenant, you can set defaults in `.env`:

```env
GOOGLE_APPLICATION_CREDENTIALS=C:\secure\cyberguard\google-service-account.json
GOOGLE_ADMIN_USER=admin@yourdomain.com
GOOGLE_WORKSPACE_DOMAIN=yourdomain.com
```

Then in **Edit tenant** you only need to check **Enable directory sync**; the tenant will use these values if it doesn’t have its own credentials path and admin user.

---

## Step 6: Use directory integration in campaigns

1. Go to **Campaigns** → **Create** (or **New campaign**).
2. Set **Target type** to **Group (Google Workspace)** or **OU (organizational unit)**.
3. The **directory integration** panel appears:
   - **Left:** Search and select **User groups** (and OUs). Use the search box to filter by name (e.g. `lcps_tech`).
   - **Right:** After selecting one or more groups/OUs, the resolved **emails** appear with a “Select all” option.
4. Select the groups (and optionally deselect some emails), then create the campaign. CyberGuard will send one simulation email per selected user and will **recursively expand nested groups**.

---

## Troubleshooting

| Issue | What to check |
|-------|----------------|
| “No groups or OUs found” | Credentials path is correct and the file is readable by the web server. Admin email is a real Workspace user with access to your domain. Domain-wide delegation scopes are added exactly as above (no typos, no extra spaces). |
| “Directory sync is not enabled” | In Edit tenant, ensure **Enable directory sync** is checked and saved. |
| “No tenant selected” | In the sidebar, use the **Tenant** dropdown to select the tenant you configured. |
| 403 or “Not authorized” | In Admin Console, confirm the **Client ID** (service account numeric ID) and **OAuth Scopes** are correct. Wait a few minutes after adding delegation. Ensure the admin user (e.g. `admin@yourdomain.com`) is a Super Admin or has Admin API access. |
| Nested groups not expanding | CyberGuard recursively expands nested groups (up to 20 levels). If a group is in another domain or you see errors in the log, check that the admin user can see those groups in Admin Console. |

---

## Security notes

- **Do not** put the service account JSON inside your web root or commit it to version control.
- Restrict file permissions so only the app/server can read the JSON (e.g. `chmod 600` on Linux).
- Use a dedicated admin user for “impersonate for API” (e.g. `cyberguard-api@yourdomain.com`) with minimal roles if possible.
- Rotate the key periodically: create a new key in Cloud Console, update the path in CyberGuard, then disable or delete the old key.
