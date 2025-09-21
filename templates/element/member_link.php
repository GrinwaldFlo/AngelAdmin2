<?php
/**
 * Member Link Element
 * 
 * Creates links that work with both authenticated and hash-authenticated users
 * 
 * @var \App\View\AppView $this
 * @var array $url URL array for the link
 * @var string $text Link text
 * @var array $options Additional HTML options for the link
 * @var bool $includeHash Whether to include hash in URL (for sharing external links)
 */

$url = $url ?? [];
$text = $text ?? '';
$options = $options ?? [];
$includeHash = $includeHash ?? false;

// Check if this is a hash-authenticated session
$isHashAuth = isset($curUser) && isset($curUser->member_id) && !isset($curRole->MemberEditAll);

if ($isHashAuth && !$includeHash) {
    // For hash-authenticated users, modify certain URLs to point to myPage
    if (isset($url['action']) && in_array($url['action'], ['view', 'edit'])) {
        $url = ['controller' => 'Members', 'action' => 'myPage'];
    }
}

echo $this->Html->link($text, $url, $options);
?>
