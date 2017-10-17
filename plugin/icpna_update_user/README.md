ICPNA update user
=================

Update user data by Web Service

Installation procedure
----------------------

1. Open `insert_user_fields.php`, comment the "exit;" line, save, and execute the script with `php insert_user_fields.php` from inside the plugin directory (preferrably), or load it in a browser (Chamilo URL + /plugin/icpna_update_user/insert_user_fields.php)

2. On Chamilo's main configuration settings page, locate the custom pages option and enable it. Please note that this plugin goes with the file custompages/profile.php. Otherwise it will probably not work as expected.

3. On Chamilo's plugin page, locate the "ICPNA Update User" plugin and enable

4. Enter the plugin's configuration page (locate the "Configure" button in the "ICPNA Update User" box), select "Yes" in "Enable hook" and enter the web service URL below. This web service URL will be used to request user data, then to send user updates. The URL should point to the WSDL of the web service (probably ending with ?wsdl). If the web service doesn't work, you should not see any option in the "Document type" drop-down in the profile edition page.

5. Flush the cache. This can be done through the interface, in the main administration page, with the option "Clear cache and temporary files" or through Chash, if you use it, with `chash fct`

6. Check that changes to the basic user fields are allowed in the "User" section of the platform settings.

7. Search `page_after_login` in *Configuration Settings* page, then set the following parameters and save configuration:
- *Learner page after login* to `plugin/icpna_update_user/redirect.php`
- *Teacher page after login* to `plugin/icpna_update_user/redirect.php`
- *Human resources manager page after login* to `plugin/icpna_update_user/redirect.php`
- *Session admin page after login* to `plugin/icpna_update_user/redirect.php`

Once all this is configured, your users' profile should be attached to an external application and any update should be sent directly there.

This has been developed through BeezNest's task system, reference BT#13382
