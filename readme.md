# EPA Media View

EPA Media View plugin is used to have a folder view in media gallery

## Installation
1. Upload the `epa-media-view` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

## Instructions

To display your custom folder generated via code in folder view use the filter "media_folder_list".

"media_folder_list" filter receives a multi dimentional array as parameter. Example: 

```php
array( array( 'name' => 'Folder Name' ) );
```

Link the uploaded attachment to the folder by saving folder name in the post meta of the attached with meta key "folder"
Note: Use the same folder name in meta key as passed in "media_folder_list" filter.
