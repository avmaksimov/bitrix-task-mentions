# Maxima Pridex Task Mentions

[Русский](README.md) · **English**

A free local module for **self-hosted Bitrix24**. When a user is `@mentioned` in a **task chat**, the module attempts to add that user as a **co-assignee** (default) or **observer**.

> This is source code, not a Bitrix24 Marketplace app. Installation and updates are manual. Compatibility with a particular portal version is not guaranteed; test on a sample task first.

## Behavior

- Handles the PHP event `im:OnAfterMessagesAdd` for task chats only (`TASKS_TASK`).
- Reads user mentions in the `[USER=123]Name[/USER]` format.
- Updates tasks **under the message author's permissions**; it never bypasses Bitrix24 access controls. If the author cannot change task participants, the update is not performed.
- Preserves existing participants, skips inactive users and the message author, and never changes an existing participant's role.
- Ignores unrelated chats, legacy forum comments, and system messages.
- Choose **co-assignees (0)** or **observers (1)** in the standard module settings. The selection is stored in the portal database.

## Installation (standard BitrixVM, Linux)

The commands below assume the standard BitrixVM paths: document root `/home/bitrix/www`, site account `bitrix`, and an already published GitHub repository `avmaksimov/bitrix-task-mentions` with the `main` branch.

### 1. Download and extract

Connect over SSH. Run these commands as `root` or with `sudo` access:

```bash
curl -fL --retry 2 -o /tmp/bitrix-task-mentions.tar.gz \
  https://github.com/avmaksimov/bitrix-task-mentions/archive/refs/heads/main.tar.gz

sudo install -d -o bitrix -g bitrix -m 755 \
  /home/bitrix/www/local /home/bitrix/www/local/modules

sudo tar -xzf /tmp/bitrix-task-mentions.tar.gz \
  -C /home/bitrix/www --strip-components=1 \
  bitrix-task-mentions-main/local/modules/maximapridex.taskmentions
```

The full repository archive is downloaded to `/tmp/bitrix-task-mentions.tar.gz`, but the `tar` command extracts **only the module directory**. README, LICENSE, and other repository files are not copied into the website root.

The resulting module directory must be:

`/home/bitrix/www/local/modules/maximapridex.taskmentions/`

Set the correct owner and permissions, **especially if you extracted files as `root`**:

```bash
sudo chown -R bitrix:bitrix /home/bitrix/www/local/modules/maximapridex.taskmentions
sudo find /home/bitrix/www/local/modules/maximapridex.taskmentions -type d -exec chmod 755 {} +
sudo find /home/bitrix/www/local/modules/maximapridex.taskmentions -type f -exec chmod 644 {} +
```

Directories use mode `755`, regular files `644`. Do not use `777`. `/local/` is inside the Bitrix24 web document root, not the Linux filesystem root.

### 2. Verify nginx protection for `/local/`

`/local/` lives inside the web root. Module PHP files should not be directly accessible through HTTP. The module includes an Apache `.htaccess` and `B_PROLOG_INCLUDED` guards; **nginx does not read `.htaccess`**.

Replace `portal.example.com` with **your portal domain**:

```bash
curl -sS -o /dev/null -w '%{http_code}\n' https://portal.example.com/local/
curl -sS -o /dev/null -w '%{http_code}\n' https://portal.example.com/local/modules/maximapridex.taskmentions/include.php
```

Both requests should return `403`. If they do, and you have verified that the nginx rule applies to the correct virtual host, proceed to step 3. A `403` alone is not conclusive evidence of nginx-level protection; inspect the configuration if in doubt.

**If access is not denied:** standard BitrixVM installations can use a separate nginx configuration file. First, verify that the relevant HTTPS virtual host includes `site_settings/default`:

```bash
sudo grep -nF 'include bx/site_settings/default/*.conf' /etc/nginx/bx/site_enabled/ssl.s1.conf
```

If this file/include does not exist, or the configuration differs, **do not apply the following commands blindly**; ask your server administrator. Also check whether other modules serve required JS/CSS, images, or AJAX handlers from `/local/`: a blanket deny will block those resources unless you add safe, specific exceptions.

Check whether `/etc/nginx/bx/site_settings/default/local_security.conf` already exists. **Never overwrite an existing file without reviewing it.** If it does not exist, create it:

```bash
sudo tee /etc/nginx/bx/site_settings/default/local_security.conf > /dev/null <<'EOF'
# Deny direct HTTP access to /local
location = /local {
    return 403;
}

location ^~ /local/ {
    return 403;
}
EOF

sudo chown root:root /etc/nginx/bx/site_settings/default/local_security.conf
sudo chmod 644 /etc/nginx/bx/site_settings/default/local_security.conf
sudo nginx -t
```

**If `nginx -t` fails:** do not reload nginx. If you *just created* this file and it did not exist before, remove it:

```bash
sudo rm /etc/nginx/bx/site_settings/default/local_security.conf
sudo nginx -t
```

Ask a server administrator to adjust the configuration.

**If `nginx -t` succeeds:** apply the configuration and repeat both HTTP checks from the start of step 2:

```bash
sudo systemctl reload nginx
```

Expect `403` for the directory and the PHP file. If the result differs, ask the administrator to inspect the virtual host and `location` precedence. Do not block all of `/local/` without considering other portal resources.

### 3. Register the module in Bitrix24

Sign in as a portal administrator. In the self-hosted Bitrix24 interface, navigate to **Marketplace → menu (⋯ / More) → Install** and install **Maxima Pridex: Task Mentions**. The exact menu labels may vary between portal versions.

Copying files does not register the PHP event handler. This step is required **once** for the initial installation.

### 4. Configure and test

Open **Settings → Product settings → Module settings** and select the module, or use this admin page (replace the domain):

`https://portal.example.com/bitrix/admin/settings.php?mid=maximapridex.taskmentions&lang=en`

Choose **Co-assignees** (default) or **Observers**, then save. In a test task, `@mention` a user and verify that they are added. Also try a mention written by an assignee: permissions belong to the **message author**, not only the task creator.

## Manual updates

1. Review the upstream changes and choose an appropriate maintenance window.
2. Repeat the commands in **step 1**: download the latest archive, extract it, and set file permissions. Existing files with matching names will be overwritten. Files deleted by an upstream release are **not automatically removed**, so review and remove obsolete files manually if needed.
3. The module is already registered; **do not install it again**. Database-stored settings are preserved.
4. If necessary, clear PHP OPcache or restart PHP workers by your standard procedure, then retest `@mentions`.

**Updates are manual.** Review the changes and archive source before updating; use a trusted public repository.

## Removal

Uninstall the module through the Bitrix24 interface first, which unregisters its event handler. You may then remove `/home/bitrix/www/local/modules/maximapridex.taskmentions/`. Previously added task participants remain. **Keep the nginx protection** for `/local/` after removing the module; it protects the portal as a whole.

## Limitations

Availability and payload of task-chat events vary by self-hosted Bitrix24 version. Concurrent participant updates may conflict. Errors updating the task should not prevent chat messages from being sent. Users without permission to edit task participants cannot add users automatically.

License: [MIT](LICENSE).
