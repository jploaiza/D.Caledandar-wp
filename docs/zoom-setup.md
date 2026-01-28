# Zoom Integration Setup Guide

## 1. Create a Zoom App
1. Go to [Zoom App Marketplace](https://marketplace.zoom.us/).
2. Log in and go to **Manage** -> **Develop** -> **Build App**.
3. Choose **OAuth** as the app type.
4. Disable "Would you like to publish this app on Zoom App Marketplace?" (unless you are distributing this plugin publicly).
5. Enter a name (e.g., "Reservas Terapia Plugin").

## 2. App Credentials
1. Copy the **Client ID** and **Client Secret**.
2. Paste them into the **Reservas Terapia Settings** in your WordPress Admin.

## 3. Redirect URI & Allow List
1. In the Zoom App settings, find **Redirect URL for OAuth**.
2. Enter the URL provided in your WordPress Settings (e.g., `https://yoursite.com/wp-admin/admin.php?page=reservas-terapia`).
3. Add the same URL to the **Whitelist URL** list.

## 4. Scopes
Add the following scopes to your app:
- `meeting:write:admin` (Create and update meetings)
- `meeting:read:admin` (Read meeting details)
- `user:read:admin` (optional, for checking user details)

## 5. Feature (Webhooks)
1. Enable **Event Subscriptions**.
2. Create a new subscription called "Reservas Terapia".
3. **Event Notification Endpoint URL**: `https://yoursite.com/wp-json/rt/v1/zoom/webhook`
4. Copy the **Secret Token** and paste it into the WordPress Settings.
5. Add Events:
   - `Start Meeting` (meeting.started)
   - `End Meeting` (meeting.ended)
   - `Participant/Host joined meeting` (participant.joined)
   - `Participant/Host left meeting` (participant.left)

## 6. Finish
1. Save your Zoom App.
2. Go to WordPress Admin -> Reservas Terapia.
3. Click "Connect with Zoom".
4. Authorize the app.
