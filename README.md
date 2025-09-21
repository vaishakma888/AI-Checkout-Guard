## AI Checkout Guard

An intelligent WooCommerce plugin that uses AI to prevent Return to Origin (RTO) orders by dynamically controlling Cash on Delivery (COD) availability during checkout.

This repository contains the full source code for the AI Checkout Guard plugin.

---

## 🚀 About the Plugin

AI Checkout Guard is a smart WooCommerce extension designed to dramatically reduce RTO orders and increase prepaid conversions. It leverages an external AI API to assess customer risk in real time, enabling store owners to make data-driven decisions at the most critical stage of the checkout process.

By intelligently filtering payment methods, the plugin reduces problematic orders without negatively impacting the customer experience for genuine buyers. The result is a significant reduction in RTO, typically by 40-60%, leading to higher profitability and less manual intervention.

---

## ✨ Key Features

* **Real-time Risk Assessment:** Analyzes customer data instantly during checkout to generate a risk score.
* **Smart COD Control:** Dynamically shows, nudges, or hides Cash on Delivery based on the risk level of the order.
* **Optional Verification:** For high-risk orders, customers can be required to complete an additional verification step via WhatsApp or SMS OTP.
* **Prepaid Incentives:** Gentle nudges encourage safer prepaid payment methods for medium-risk customers, with the option to add a COD fee.
* **Advanced Analytics:** A comprehensive dashboard provides detailed insights into RTO reduction, prepaid conversions, and estimated savings.
* **Seamless Integration:** Works with modern WooCommerce Checkout Blocks, ensuring compatibility with most themes.

---

## 🛠️ Installation and Configuration

1.  **Download:** Download the plugin from this repository.
2.  **Upload:** In your WordPress dashboard, go to **Plugins** > **Add New** > **Upload Plugin** and select the downloaded ZIP file.
3.  **Activate:** Activate "AI Checkout Guard" from your plugins list.
4.  **Configure:** Navigate to **WooCommerce** > **AI Checkout Guard** to configure the settings.
5.  **API Details:** Enter your Risk API URL and authentication key from your chosen risk assessment service.
6.  **Set Policies:** Define the risk score thresholds (0-100) and choose the corresponding actions for low, medium, and high-risk orders.
7.  **Test:** Place a few test orders to verify the risk filtering works correctly.

---

## 📂 Project Structure

The codebase is organized using a clear, modular structure for maintainability and extensibility.

* `ai-checkout-guard.php`: The main entry file for the plugin.
* `includes/`: Contains core logic and main classes for the plugin's functionality.
    * `class-risk-api.php`: Handles all communication with the external risk assessment API.
    * `class-settings.php`: Manages the plugin's settings, including API keys and risk thresholds.
* `admin/`: Holds all files related to the WordPress admin dashboard and settings pages.
    * `admin-menu.php`: Creates the admin menu item.
    * `class-admin-settings.php`: Renders and manages the plugin's settings page.
* `public/`: Manages all front-end functionality, especially for the checkout process.
    * `class-checkout-filter.php`: Contains the logic to filter and modify payment methods based on the risk score.
* `integrations/`: Houses classes for optional integrations with external services.
    * `class-whatsapp-api.php`: Handles the WhatsApp confirmation system for high-risk orders.
    * `class-webhooks.php`: Manages webhooks for receiving order status updates and feeding them back to the risk service.
* `assets/`: Stores media files such as icons and banners.

---

## 🤝 Contributing

We welcome contributions from the community! If you've found a bug, have a feature suggestion, or want to contribute code, please open an issue or submit a pull request.

---

## 📜 License

This plugin is released under the **GPLv2 or later** license. For more details, see the `LICENSE` file in this repository.
