# Iconify Plugin for Craft CMS


A plugin that helps you to use [Iconify](https://iconify.design/) in Craft CMS.


## Requirements

---

 - Craft CMS 5.0.0 or later
 - PHP 8.2 or later


## Installation

---


Open your terminal and run the following commands:

```bash
cd /path/to/my-project

composer require craftyfm/iconify

php craft install/plugin iconify
````


## Setup

---
To be able to use this plugins, you need to set up the storage and the icon sets you want to use.
Please follow these following steps:
1. Go to **Settings → Plugins → Iconify**.
2. Choose your preferred **storage option**:

    * **Local Storage**: Icons are saved in `storage/` folder.
   
       If you're using containers (e.g., Docker), icons may need to be re-downloaded unless storage is persisted.
    * **Database**: Icons are stored in the database.

       Using large icon sets may increase your database size and affect performance.
3. Select the **icon sets** you want to use.

---

## Downloading Icons

To download icons, you have two options:

- Navigate to **Utilities → Iconify** in the Craft Control Panel
- Or use the console command:

```bash
./craft iconify/download {iconset}
```
Leave {iconset} empty to download all the selected icon sets from the plugin settings.

---

## Twig Usage

Use the `iconify()` Twig function to render icons in your templates:

```twig
{{ iconify('home', 'tabler', '#000', '1.5', 24, 24) }}
```

### Parameters:

* `iconName` (required): The name of the icon.
* `iconset` (required): The icon set (e.g., `tabler`, `mdi`, etc.).
* `color` (optional): The fill or stroke color (e.g., `#ff0000` or `red`).
* `stroke` (optional): Stroke width (e.g., `1`, `1.5`, `2`).
* `width` (optional) Icon width (e.g., 24, `24`. `1em`)
* `height` (optional) Icon height (e.g., 24, `24`. `1em`)


## Iconify Field Picker

---


You can create a iconify field picker in the Control Panel:
Currently, we support these configurable features:
    * Icon color
    * Stroke width
    * Icon set(s)

To show the icon in your templates, use the `getSvg()` method:

```twig
{{ entry.fieldHandle.getSvg() }}
```

---