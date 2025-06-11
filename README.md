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


## 🖥️ Admin UI Page: Order Status Change History

- The module provides an **admin grid** under **Sales > Order Status Change History**.
- The grid shows a history of all order status changes (from the custom table).
- **Features:**
  - Filters for each column (ID, Order Number, Old Status, New Status, Date)
  - Mass delete action
  - Enable, disable are just for show casing the mass action handling, nothing will change in DB.
- Fully built using Magento 2 UI components and data providers.

---

## 🗄️ Caching & Identity Interface

- The module implements Magento’s [`IdentityInterface`](https://developer.adobe.com/commerce/php/development/components/cache/identity-interface/) for the `OrderStatusHistory` model.
- Each status history record is tagged with a unique cache tag (e.g., `custom_order_status_history_123`).
- **automatically invalidated** when a status history record is created or deleted.
- For advanced scenarios, the module includes examples of using Magento’s [Cache API](https://developer.adobe.com/commerce/php/development/components/cache/custom-caching/) for caching frequently accessed order history.

---

## 🧪 Testing

### Unit Testing

- All core business logic is covered by **unit tests**.
- Test files are located under:\
  `dev/tests/unit/Vendor/CustomOrderProcessing/Model/`
- **Examples tested:**
  - Status update success
  - No status change
  - Status update with email notification
  - Exception handling for invalid input

### Integration Testing

- End-to-end integration tests ensure module functionality with real Magento data.
- Test files are under:\
  `dev/tests/integration/testsuite/Vendor/CustomOrderProcessing/Observer/`
- **Examples tested:**
  - Observer logs changes to custom table on order status change

#### Running Tests

```bash
# Unit tests
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist dev/tests/unit/Vendor/CustomOrderProcessing/Model/OrderStatusManagementTest.php

# Integration tests (require test DB config)
vendor/bin/phpunit -c /var/www/html/mage246/dev/tests/integration/phpunit.xml dev/tests/integration/testsuite/Vendor/CustomOrderProcessing/Observer/OrderStatusHistoryTest.php
```