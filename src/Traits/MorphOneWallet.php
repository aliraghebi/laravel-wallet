<?php

namespace AliRaghebi\Wallet\Traits;

use AliRaghebi\Wallet\Contracts\Services\CastServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\ClockServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\ConsistencyServiceInterface;
use AliRaghebi\Wallet\Contracts\Services\IdentifierFactoryServiceInterface;
use AliRaghebi\Wallet\Models\Wallet as WalletModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Trait MorphOneWallet.
 *
 * @property WalletModel $wallet
 *
 * @psalm-require-extends Model
 */
trait MorphOneWallet {
    /**
     * Get default Wallet. This method is used for Eager Loading.
     *
     * @return MorphOne<WalletModel>
     */
    public function wallet(): MorphOne {
        // Get the CastService instance from the application container.
        $castService = app(CastServiceInterface::class);
        $consistencyService = app(ConsistencyServiceInterface::class);

        /**
         * Get the related wallet model class name from the configuration.
         * If not found, use the default wallet model class name.
         *
         * @var class-string<WalletModel> $related
         */
        $related = config('wallet.wallet.model', WalletModel::class);

        // Eager load the wallet for the related model.
        return $castService
            ->getHolder($this) // Get the related holder model.
            ->morphOne($related, 'holder') // Define the Eloquent relationship.
            ->withTrashed() // Include soft deleted wallets.
            ->where('slug', config('wallet.wallet.default.slug', 'default')) // Filter by the default wallet slug.
            ->withDefault(static function (WalletModel $wallet, object $holder) use (
                $consistencyService,
                $castService
            ) { // Define the default wallet values.
                // Get the related model.
                $model = $castService->getModel($holder);

                $slug = config('wallet.wallet.default.slug', 'default');
                $uuid = app(IdentifierFactoryServiceInterface::class)->generate();
                $time = app(ClockServiceInterface::class)->now();

                $checksum = $consistencyService->createWalletChecksum($uuid, '0', '0', $time);

                // Fill the default wallet attributes.
                $wallet->forceFill([
                    'uuid' => $uuid,
                    'name' => config('wallet.wallet.default.name', 'Default Wallet'), // Default wallet name.
                    'slug' => $slug, // Default wallet slug.
                    'meta' => config('wallet.wallet.default.meta', []), // Default wallet metadata.
                    'balance' => 0, // Default wallet balance.
                    'frozen_amount' => 0,
                    'checksum' => $checksum,
                    'created_at' => $time,
                    'updated_at' => $time,
                ]);

                if ($model->exists) {
                    // Set the related model on the wallet.
                    $wallet->setRelation('holder', $model->withoutRelations());
                }
            });
    }

    /**
     * Get the wallet attribute.
     *
     * @return WalletModel|null The wallet model associated with the related model.
     */
    public function getWalletAttribute(): ?WalletModel {
        /**
         * Retrieve the wallet model associated with the related model.
         *
         * @var WalletModel|null $wallet
         */
        $wallet = $this->getRelationValue('wallet');

        // If the wallet model exists and the 'holder' relationship is not loaded,
        // associate the related model with the wallet.
        if ($wallet && !$wallet->relationLoaded('holder')) {
            // Get the related holder model.
            $holder = app(CastServiceInterface::class)->getHolder($this);

            // Associate the related model with the wallet.
            $wallet->setRelation('holder', $holder->withoutRelations());
        }

        return $wallet;
    }
}
