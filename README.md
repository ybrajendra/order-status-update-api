# Custom Order Status Update Module

This Magento 2 module provides a custom API endpoint to update the status of an order using its entity ID. It follows Magento's service contract architecture and is useful for integrations or automated workflows where external systems need to update order statuses programmatically.

---

## 🧩 Features

- Adds a REST API endpoint: `/V1/customorder/update-status`
- Updates order status with validation against allowed status against order state
- Adds a comment to the order history
- Optionally sends an order update email (e.g., when status is "shipped")
- Also log changes into a custom table (`sales_order_status_change_history`)

---

## ⚙️ Installation

1. Copy the `Vendor/CustomOrderProcessing` folder into your Magento `app/code/` directory.
2. Run the following commands from your Magento root directory:

```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

---

## 📡 API Usage

### Endpoint

```
POST /rest/V1/customorder/update-status
```

### Request Body

```json
{
  "order_id": "10",
  "status": "out_for_delivery"
}
```

### Successful Response

```
true
```

### Possible Errors

- HTTP 404 `Order does not exist` if the entity ID is invalid
- HTTP 500 if the request structure is malformed or system fails unexpectedly

---

## 🏗 Architectural Decisions

- **Service Contracts**: API interface and data interface promote loose coupling.
- **Service Layer**: Business logic is encapsulated in the `OrderStatusManagement` model.
- **API Exposure**: The method is exposed securely using `webapi.xml`, allowing REST access.
- **Validation**: Ensures structure and safety when parsing incoming requests.
- **Extensibility**: Built to be easily extensible via plugins or preference overrides without modifying core logic.
