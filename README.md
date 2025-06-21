# Custom Order Status Update Module

This Magento 2 module provides a custom API endpoint to update the status of an order using its entity ID. It follows Magento's service contract architecture and is useful for integrations or automated workflows where external systems need to update order statuses programmatically.

---

## 🧩 Features

- Adds a REST API endpoint: `/V1/customorder/update-status`
- Updates order status with validation against allowed status and order state
- Adds a comment to the order history
- Optionally sends an order update email (e.g., when status is "shipped")
- Logs changes into a custom table (`sales_order_status_change_history`)
- Displays order status change history in a Magento admin grid
- Supports cache invalidation using Magento’s `IdentityInterface`
- Includes unit and integration tests for robust, reliable code
- **Custom log file** for module-specific events/errors: `var/log/custom_order_processing.log`
- **API Rate Limiting** to protect the endpoint from abuse

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
    "request": {
        "order_id": "2",
        "status": "preparing_order"
    }
}
```

### Successful Response

```json
{
    "success": true,
    "message": "Order status updated successfully to processing.",
    "code": 200,
    "result": [
        "{\"entity_id\":\"4\",\"order_id\":\"2\",\"old_status\":\"pending\",\"new_status\":\"processing\",\"created_at\":\"2025-06-11 02:04:31\",\"order_number\":\"000000001\"}",
        "{\"entity_id\":\"6\",\"order_id\":\"2\",\"old_status\":\"processing\",\"new_status\":\"preparing_order\",\"created_at\":\"2025-06-11 04:56:02\",\"order_number\":\"000000001\"}",
        "{\"entity_id\":\"19\",\"order_id\":\"2\",\"old_status\":\"preparing_order\",\"new_status\":\"processing\",\"created_at\":\"2025-06-21 04:23:06\",\"order_number\":\"000000001\"}"
    ]
}
```

### Error Response (Example)

```json
{
  "success": false,
  "message": "Order status is already set to preparing_order.",
  "code": 206,
  "result": []
}
```

#### Common Error Codes & Messages

| Code | Message                                                       |
| ---- | ------------------------------------------------------------- |
| 201  | Order ID cannot be empty.                                     |
| 202  | Invalid Order ID format.                                      |
| 203  | Status cannot be empty.                                       |
| 204  | Invalid order status format.                                  |
| 205  | Order with ID X does not exist.                               |
| 206  | Order status is already set to X.                             |
| 207  | Status transition from X to Y is not allowed.                 |
| 208  | Failed to retrieve order status history for order ID X.       |
| 209  | An unexpected error occurred: ...                             |
| 210  | Rate limit exceeded. Please wait before making more requests. |

---

## 🏗 Architectural Decisions

- **Service Contracts**: API interface and data interface promote loose coupling.
- **Service Layer**: Business logic is encapsulated in the `OrderStatusManagement` model.
- **API Exposure**: The method is exposed securely using `webapi.xml`, allowing REST access.
- **Validation**: Ensures structure and safety when parsing incoming requests.
- **Extensibility**: Built to be easily extensible via plugins or preference overrides without modifying core logic.

---

## 🖥️ Admin UI Page: Order Status Change History

- Provides an **admin grid** under **Sales > Order Status Change History**.
- The grid shows a history of all order status changes (from the custom table).
- **Features:**
  - Filters for each column (ID, Order Number, Old Status, New Status, Date)
  - Mass delete action
  - Friendly status labels (e.g., “Preparing order”)
- Built using Magento 2 UI components and data providers.

---

## 🚦 Rate Limiting

- The API enforces rate limiting per client/IP to prevent abuse (default: e.g. 100 requests per minute, configurable).
- Rate-limited requests receive a 210 code and an appropriate message (see above).
- Limit can be adjusted from **store->configuration->Custom Order Processing->API Settings**.
- Rate limiting is handled **in the service**, so error format is always consistent.

---

## 🗄️ Caching & Identity Interface

- Implements Magento’s [`IdentityInterface`](https://developer.adobe.com/commerce/php/development/components/cache/identity-interface/) for the `OrderStatusHistory` model.
- Each status history record is tagged with a unique cache tag (e.g., custom_order_status_history_123).
- **Automatically invalidated** when a status history record is created or deleted.
- Advanced scenarios: example of using Magento’s [Cache API](https://developer.adobe.com/commerce/php/development/components/cache/custom-caching/) for frequently accessed data.

---

## 🧪 Testing

### Unit Testing

- All core business logic is covered by **unit tests**.
- Test files are located under: `dev/tests/unit/Vendor/CustomOrderProcessing/Test/Unit/Model/`
- **Examples tested:**
  - Status update success
  - No status change
  - Email notifications
  - Exception handling for invalid input

### Integration Testing

- End-to-end integration tests ensure module functionality with real Magento data.
- Test files are under: `dev/tests/integration/testsuite/Vendor/CustomOrderProcessing/Test/Integration/`
- **Examples tested:**
  - Observer logs changes to custom table
  - API endpoint updates status and triggers log/notifications

#### Running Tests

```bash
# Unit tests
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist dev/tests/unit/Vendor/CustomOrderProcessing/Model/OrderStatusManagementTest.php

# Integration tests (require test DB config)
vendor/bin/phpunit -c /var/www/html/mage246/dev/tests/integration/phpunit.xml dev/tests/integration/testsuite/Vendor/CustomOrderProcessing/Observer/OrderStatusHistoryTest.php
```
---

## 📝 Custom Log File

- All custom events and errors are logged to `var/log/custom_order_processing.log`.
- The logger is injected as `Vendor\CustomOrderProcessing\Logger\Logger`.
- Example usage:
  ```php
  $this->logger->info('Order status updated', ['order_id' => $orderId]);
  $this->logger->error('Status update failed', ['exception' => $e->getMessage()]);
  ```
---