<?php
/**
 * Resolve the presentation gateway for a Genesis NFT image.
 *
 * Plain language for DEVS FOR DECADES:
 * This changes only the URL used to display a known immutable IPFS asset.
 * It never changes the NFT CID/path, ownership, verification, metadata,
 * Traits, Abilities, Lab progression, staking, or any economy state.
 */
function narrrfs_resolve_genesis_image_url(string $collection, $imageUrl): string
{
    $url = trim((string)$imageUrl);

    if (strtolower(trim($collection)) !== 'genesis' || $url === '') {
        return $url;
    }

    $matched = preg_match(
        '~^https://([A-Za-z0-9]+)\.ipfs\.w3s\.link(/[^\s?#]+)(\?[^\s#]*)?(#\S*)?$~',
        $url,
        $parts
    );

    if ($matched !== 1) {
        return $url;
    }

    return 'https://gensuki.4everland.link/ipfs/'
        . $parts[1]
        . $parts[2]
        . ($parts[3] ?? '')
        . ($parts[4] ?? '');
}
