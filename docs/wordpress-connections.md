# WordPress Connections

This document explains how to set up WordPress connections in the MetriFi API.

## Overview

WordPress connections allow you to connect to WordPress websites using application passwords. This enables the API to interact with WordPress sites for data collection and analysis.

## Required Data

To create a WordPress connection, you need the following information:

- **WordPress URL**: The URL of your WordPress website (e.g., https://example.com)
- **Username**: Your WordPress admin username
- **Application Password**: An application password generated from your WordPress admin panel

## Creating an Application Password in WordPress

1. Log in to your WordPress admin panel
2. Go to Users → Profile
3. Scroll down to the "Application Passwords" section
4. Enter a name for the application (e.g., "MetriFi API")
5. Click "Add New Application Password"
6. Copy the generated password (you will only see it once)

## API Usage

To create a WordPress connection, make a POST request to:

```
POST /api/{organization-slug}/connections
```

With the following JSON body:

```json
{
  "service": "WordPress Website",
  "name": "My WordPress Site",
  "token": {
    "wordpress_url": "https://example.com",
    "username": "admin",
    "app_password": "xxxx xxxx xxxx xxxx xxxx xxxx"
  }
}
```

## Security Considerations

- Application passwords are stored securely in the database
- Application passwords have limited permissions based on how they were created in WordPress
- You can revoke application passwords at any time from your WordPress admin panel
