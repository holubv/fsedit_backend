https://fsedit.cf

```sql
CREATE TABLE `x_request_limiter` (
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `key` varchar(32) DEFAULT NULL,
  `ip` varchar(45) NOT NULL,
  KEY `key` (`key`),
  KEY `time` (`time`)
)
```

## todo

- rate limiting