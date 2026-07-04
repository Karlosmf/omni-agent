#!/bin/bash
set -x

# Remove single quotes inside the double quotes for the password
FTP_URL="ftp://yer929:x0MVh8*GqxWZ@server.dns-principal-33.com"
LOCAL_FILE="/Users/carlitos/Projects/omni-agent/config/livewire.php"

# 1. Upload config to both locations
curl -T "$LOCAL_FILE" "$FTP_URL/public_html/config/livewire.php"
curl -T "$LOCAL_FILE" "$FTP_URL/config/livewire.php"

# 2. Change permissions for public_html/public/assets/branding
curl "$FTP_URL/" -Q "SITE CHMOD 775 public_html/public/assets/branding" || true
# 3. Change permissions for public/assets/branding
curl "$FTP_URL/" -Q "SITE CHMOD 775 public/assets/branding" || true

# 4. Delete cache files
curl "$FTP_URL/" -Q "DELE public_html/bootstrap/cache/config.php" || true
curl "$FTP_URL/" -Q "DELE bootstrap/cache/config.php" || true

echo "Done"
