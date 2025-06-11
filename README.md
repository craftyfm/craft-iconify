# Iconify Plugin for Craft CMS


A plugin that simplifies using icons from [Iconify](https://iconify.design/) in Craft CMS.

---

## 🚀 Installation

You can install the plugin via Composer:

```bash
composer require craftyfm/iconify
````

Then enable the plugin in the Craft Control Panel under **Settings > Plugins**.

---

## ⚙️ Configuration

After installation:

1. Go to **Settings → Plugins → Iconify**.
2. Choose your preferred **storage option**:

    * **Local Storage**: Icons are saved in `storage/` folder.
   
      ⚠️ If you're using containers (e.g., Docker), icons may need to be re-downloaded unless storage is persisted.
    * **Database**: Icons are stored in the database.

      ⚠️ Using large icon sets may increase your database size and affect performance.
3. Select the **icon sets** you want to use.

---

## 📥 Downloading Icons

To download icons, you have two options:

- Navigate to **Utilities → Iconify** in the Craft Control Panel
- Or use the console command:

```bash
./craft iconify/download {iconset}

```
Leave {iconset} empty to download only the selected icon sets from the plugin settings.

---

## 🧩 Twig Usage

Use the `iconify()` Twig function to render icons in your templates:

```twig
{{ iconify('home', 'tabler', '#000', '1.5') }}
```

### Parameters:

* `iconName` (required): The name of the icon.
* `iconset` (required): The icon set (e.g., `tabler`, `mdi`, etc.).
* `color` (optional): The fill or stroke color (e.g., `#ff0000` or `red`).
* `stroke` (optional): Stroke width (e.g., `1`, `1.5`, `2`).

---

## 🧱 Custom Field Type

When creating a new field in the Control Panel:

1. Choose **"Iconify"** as the field type.
2. Configure:

    * Icon color
    * Stroke width
    * Icon set(s)
3. The field shows a searchable list of icons with previews.

Use the selected icon in your templates just like any other field:

```twig
{{ entry.fieldHandle.getSvg() }}
```

---