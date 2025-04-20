# Consistency Check

The wallet's integrity and consistency are ensured using checksums. A checksum is automatically generated based on the
wallet data whenever it is updated. If the wallet state is modified externally—outside of this package—or multiple
operations are performed simultaneously (which should be prevented through the use of atomic transactions), a checksum
mismatch will occur, causing the transaction to fail and roll back.

While this feature greatly enhances data reliability, it may introduce a slight performance overhead. You can disable it
by setting consistency.enabled to false in your configuration file. However, this is not recommended unless you
specifically need to optimize for transaction speed.

Additionally, there is a configuration option for the secret used to generate checksum hashes. **It is strongly
recommended to set a strong, secure secret and avoid changing it, as modifying the secret will invalidate all previously
generated checksums.**

```php
'consistency' => [
    'enabled' => env('WALLET_CONSISTENCY_CHECK_ENABLED', true),
    'secret' => env('WALLET_CONSISTENCY_SECRET', 'consistency_secret'),
]
```