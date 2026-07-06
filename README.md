```markdown
Generate keypair for signing
```bash
openssl genrsa -out stock_api_private_key.pem 2048
openssl rsa -in stock_api_private_key.pem -pubout -out stock_api_public_key.pem
```

### Updating Product Available Quantity
```text
update_product_available_quantity(
  IN p_product_id BIGINT UNSIGNED,
  IN p_quantity INT,
  IN p_type ENUM('UPDATE', 'SET'),
  IN p_remark TEXT,
  OUT p_success TINYINT(1), // 0 = false, 1 = true
  OUT p_message TEXT
)
```
```sql
CALL update_product_available_quantity('product_id', 'quantity', 'UPDATE', 'Stock added', @success, @message);
SELECT @success, @message;
```
