<?php

namespace AliRaghebi\Wallet\Traits;

use AliRaghebi\Wallet\Contracts\Models\Wallet;
use AliRaghebi\Wallet\Contracts\Services\CastServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\WalletServiceInterface;
use AliRaghebi\Wallet\Exceptions\ModelNotFoundException;
use AliRaghebi\Wallet\Models\Wallet as WalletModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

use function array_key_exists;
use function config;

/**
 * Trait HasWallet To use a trait, you must add HasWallet trait.
 *
 * @property Collection<WalletModel> $wallets
 *
 * @psalm-require-extends Model
 */
trait HasWallet {
    use MorphOneWallet, WalletFunctions;

    /**
     * Cache for the wallets to avoid requesting them multiple times. WalletProxy stores the money wallets
     * in memory to avoid errors when you purchase/transfer, etc.
     *
     * @var array<string,WalletModel>
     */
    private array $_wallets = [];

    /**
     * Get wallet by slug.
     *
     * @param  string|null  $slug  The slug of the wallet.
     * @return WalletModel|null The wallet with the given slug, or null if not found.
     *
     * This method is a wrapper around the getWalletOrFail method. It catches the ModelNotFoundException
     * and returns null instead of throwing it.
     */
    public function getWallet(?string $slug = null): ?WalletModel {
        // Try to get the wallet with the given slug.
        try {
            return $this->findOrFailWallet($slug);
        } catch (ModelNotFoundException $exception) {
            // If the wallet is not found, return null.
            return null;
        }
    }

    /**
     * Get wallet by slug.
     *
     * This method loads wallets from the database if they are not loaded yet.
     * Then it retrieves the wallet with the given slug from the cache.
     * If the wallet is not found in the cache, it retrieves it from the database,
     * stores it in the cache, and returns it.
     *
     * @param  string|null  $slug  The slug of the wallet.
     * @return WalletModel The wallet with the given slug.
     */
    public function findOrFailWallet(?string $slug = null): WalletModel {
        $slug ??= config('wallet.wallet.default.slug', 'default');

        // Check if wallets are loaded.
        // Load wallets if they are not loaded yet.
        if ($this->_wallets === [] && $this->relationLoaded('wallets')) {
            /** @var Collection<WalletModel> $wallets */
            $wallets = $this->getRelation('wallets');
            // Load the wallets into the cache.
            foreach ($wallets as $wallet) {
                $wallet->setRelation('holder', $this->withoutRelations());
                $this->_wallets[$wallet->slug] = $wallet;
            }
        }

        // Check if the wallet is not found in the cache.
        if (!array_key_exists($slug, $this->_wallets)) {
            // Retrieve the wallet from the database if it is not found in the cache.
            $wallet = app(WalletServiceInterface::class)->findOrFailBySlug($this, $slug);
            $wallet->setRelation('holder', $this->withoutRelations());

            // Store the wallet in the cache.
            $this->_wallets[$slug] = $wallet;
        }

        // Return the wallet from the cache.
        return $this->_wallets[$slug];
    }

    /**
     * Method for obtaining all wallets.
     *
     * This method returns a MorphMany relationship object. The relationship is
     * defined between the current model (the "holder") and the wallet model.
     * The wallet model is specified in the configuration file under the
     * 'wallet.model' key. If the key is not found, the default wallet model is
     * used.
     *
     * @return MorphMany<WalletModel> The MorphMany relationship object.
     */
    public function wallets(): MorphMany {
        // Define a MorphMany relationship between the current model (the "holder")
        // and the wallet model.
        return $this->morphMany(
            // Get the wallet model from the configuration.
            config('wallet.wallet.model', WalletModel::class),
            // Specify the name of the polymorphic relation.
            'holder'
        );
    }

    /**
     * Creates a new wallet for the current model.
     *
     * This method creates a new wallet with the given data and associates it
     * with the current model. The current model is referred to as the "holder"
     * of the wallet.
     *
     * The method can be used to create a new wallet with the following data:
     *
     * - name: The name of the wallet.
     * - slug: The slug of the wallet. If not specified, the slug is generated
     *         automatically.
     * - description: The description of the wallet.
     * - meta: The meta data for the wallet. The meta data is an array of
     *         key-value pairs.
     * - decimal_places: The number of decimal places for the wallet. If not
     *                   specified, the default value is 2.
     *
     * @return WalletModel The new wallet object.
     */
    public function createWallet(
        ?string $name = null,
        ?string $slug = null,
        ?int $decimalPlaces = null,
        ?string $description = null,
        ?array $meta = null,
        ?string $uuid = null
    ): WalletModel {
        // Get holder model
        $holder = app(CastServiceInterface::class)->getHolder($this);

        // Create the wallet with the given data.
        $wallet = app(WalletServiceInterface::class)->createWallet($holder, $name, $slug, $decimalPlaces, $description, $meta, $uuid);

        // Cache the wallet.
        $this->_wallets[$wallet->slug] = $wallet;

        // Set the relation between the wallet and the current model.
        $wallet->setRelation('holder', $holder->withoutRelations());

        return $wallet;
    }

    /**
     * Checks the existence of a wallet.
     *
     * This method checks if a wallet with the given slug exists for the current model.
     *
     * @param  string  $slug  The slug of the wallet.
     * @return bool Returns true if the wallet exists, false otherwise.
     */
    public function hasWallet(string $slug): bool {
        // Check if the wallet exists by calling the getWallet() method.
        // The getWallet() method returns the wallet object if it exists,
        // or null if it does not exist.
        // Casting the result to a boolean converts null to false and the wallet
        // object to true.
        return (bool) $this->getWallet($slug);
    }
}
