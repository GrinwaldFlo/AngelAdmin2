# Second-hand items manager

## Scope

This module is available to all members and past members (`active = 0`). Users must be identified to see it, either:

- logged in with a user account, or
- through a member hash link (members without a user account).

All data (seller profile, items, accepted terms) belongs to the **Member**, not to the User.

The module can be enabled or disabled from the [admin page](#admin). When disabled, the menu button and all pages are hidden.

## Purpose

Lets members buy and sell items (uniforms, shoes, ...):

- Past members can sell uniforms they no longer need.
- Growing members can sell uniforms that are too small and buy bigger ones.

For young members, parents handle the sale. The child's name is never shown; only the name entered in the seller form (see [Before selling](#before-selling)) is displayed.

## Menu

The module has its own button **"Second-hand"** in the main menu, next to "My account" ("Mon compte").

## Rules

Before selling or buying, users must accept the following terms. Both buyers and sellers must accept them. They are accepted **only once** per member, and the acceptance is stored.

- This platform only puts buyers and sellers in contact.
- No money goes through the club.
- Buyers and sellers arrange to meet or ship the item on their own.
- All communication happens outside this website.
- We recommend meeting in person, so you can check the item and handle the payment safely.

## Selling

### Before selling

Sellers must fill in a form with:

- **Name** shown in the listing
- **Phone number**, with a checkbox "Prefer to be contacted by WhatsApp"
- **Email**: pre-filled with the member's main email (`member->email`), which is considered verified. Any other email must be validated through a confirmation link before the seller can publish items.
- **City** (free text)

This information is shown to buyers while they browse.

### Listing

Shows all items the seller currently has for sale. The seller can:

- **Mark an item as sold**: it disappears for buyers.
- **Remove an item**: the item and its picture are deleted for good.

### Add an item

All fields are mandatory:

- **Category** (list managed by the admin)
- **Description** (free text)
- **Size** (free text)
- **Price** (in CHF)
- **Picture**: one picture per item, resized to a maximum of 1200px

### Expiration

Items expire automatically after a duration set by the admin (6 months by default). Expired items are no longer shown to buyers.

## Buying

### Browsing

Shows all items for sale (not sold, not expired) with their picture, price and size, with a filter by category.

### Detail

- Shows the item with a large picture.
- Provides a link to call the seller by phone; if the seller checked WhatsApp, also shows a WhatsApp link.
- Provides a link to send an email, pre-filled with a subject about the item and a default message.

## Admin

A dedicated page, only for the `Admin` role, to:

- **Enable or disable** the module.
- **Set the expiration duration** for items (default: 6 months).
- **Manage categories** (add, edit, delete).
- **View a log of all created items**, including sold, removed and expired ones. The log is a dedicated table that keeps a copy of each item's text (seller name, category, description, size, price, dates and final status), without the picture, so it remains after an item is deleted.
- **Delete any item**.

## Coding

- Keep the module self-contained and modify as few existing files as possible.
- Only create new tables; do not alter existing tables.
- Store the module settings (enabled, expiration duration) as key/value rows in the existing `Configurations` table (adding rows only, no schema change).
- Store pictures like the other pictures of the website: in a dedicated folder under `webroot/img/`, inside a sub-folder named with a hash so the URL cannot be guessed (same approach as `webroot/img/members/{hash}/`).
- All user-facing text must use simple, clear language.
- Translations: French and English only for now.
