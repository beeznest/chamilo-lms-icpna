# Popups

Show a popup when a user is on "My courses" page.

## Set up

1. Install the plugin.
2. Set the "menu_administrator" region to plugin.
3. Enable plugin in Configure page.
4. Fix. Set the "content_bottom" region. Run on database:
```
INSERT INTO settings_current
    SET variable = 'content_bottom',
    subkey = 'popups',
    type = 'region',
    category = 'Plugins',
    selected_value = 'popups',
    title = 'popups',
    access_url = 1,
    access_url_changeable = 1,
    access_url_locked = 0;
```

## Adding a notification

1. Go to Administration page.
2. Go to plugin adminstration in the plugins block.
