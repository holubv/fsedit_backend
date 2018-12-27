1f3870be274f6c49b3e31a0c6728957f
_bRbQiE2VmoqzhNmJcmxCA
0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ
Uz50_cntFiU
GDRn0dJrpNwd
GDRn0dJrpNwFiUn

https://fsedit.cf/GDRn0dJrpNwd


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
- database error handling - throw exception on error