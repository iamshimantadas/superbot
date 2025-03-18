# Super Bot

This plugin provides several custom hooks for managing queries, settings, and CSV data in a WordPress environment. Below is a detailed description of each hook and its functionality. Some of the most popular WordPress themes like Astra, Divi, OceanWP, GeneratePress, Neve, Hestia, Avada, Sydney, Zakra, and Twenty Twenty-One etc. could works with it perfectly along with your custom theme too!

## Hooks Overview

### 1. `delete_query`
- **Description:** Deletes a query form.
- **Location:** Inside the `page=reply_edit_remove` page.
- **Functionality:** Removes a specific query from the system.

### 2. `update_query`
- **Description:** Updates an existing query.
- **Location:** Inside the `page=reply_edit_remove` page.
- **Functionality:** Modifies the details of an existing query.

### 3. `get_reply`
- **Description:** Fetches data from the Summernote editor, along with query and tags data, to populate the update form.
- **Location:** Inside the `page=reply_edit_remove` page.
- **Functionality:** Retrieves relevant data for a specific query, allowing for updates with the fetched data.

### 4. `save_query`
- **Description:** Saves a query, along with its response and associated tags.
- **Location:** Inside the `page=reply_edit_remove` page.
- **Functionality:** Stores the query and its related data in the database.

### 5. `save_settings`
- **Description:** Saves global chat settings into the database.
- **Location:** Inside the settings form.
- **Functionality:** Updates and stores global settings for the chat functionality.

### 6. `view_settings`
- **Description:** Displays the current settings values inside the settings form.
- **Location:** Inside the settings form.
- **Functionality:** Fetches and displays saved settings for user review and updates.

### 7. `import_csv`
- **Description:** Imports data from a CSV file into the `wp_chats` table.
- **Location:** In the CSV import functionality.
- **Functionality:** Bulk imports chat data from a CSV file into the WordPress database.

### 8. `export_csv`
- **Description:** Exports table data into a CSV format.
- **Location:** In the CSV export functionality.
- **Functionality:** Allows downloading of data stored in the table as a CSV file.

## Author

**Shimanta Das**

---

### My Social Media Links

- 🤹‍♂️ [LinkedIn](https://www.linkedin.com/in/shimanta-das-497167223)
- 👹 [Facebook](https://www.facebook.com/profile.php?id=100078406112813)
- 📸 [Instagram](https://www.instagram.com/meshimanta/?hl=en)
- 🐦 [Twitter](https://mobile.twitter.com/Shimantadas247)
- 📬 [Telegram](https://t.me/microcodesofficial)
- 🎦 [YouTube](https://youtube.com/channel/UCrbf6B0CU9x-I4bQOYbJVGw)

---
