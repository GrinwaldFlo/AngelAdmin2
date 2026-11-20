# AngelAdmin2

[![CakePHP](https://img.shields.io/badge/CakePHP-5.4-red.svg?style=flat-square)](https://cakephp.org)
[![PHP](https://img.shields.io/badge/PHP-8.4+-blue.svg?style=flat-square)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)

AngelAdmin2 is a web application for running a sports club: members, teams, cities, training attendance, membership fees with Swiss QR bills, a club shop, second-hand sales and coach payments, all in one place. It is made for clubs with volunteer staff: members and parents do their own registration and updates, and the committee keeps an overview of every site.

Installation and development: see [INSTALL.md](INSTALL.md).

## ✨ Features

### 👥 Members

- **Member profiles** with contact details, photo, ID photo, documents and custom fields defined by the club
- **Online registration** for the new season, with a digital signature of the agreement (PDF generated), and batch registration by the staff
- **Access without an account**: each member gets a personal link to their page, and can ask for a login link by email
- **Optional user account** created by the member from their link
- **Member lists** by team and status (active, registered, past), photo gallery, birthdays, new members, email lists
- **Family discount** list for families with several members
- **Registration invitations** sent by email to the members of a team, and cancellation of a registration

### 🏙️ Sites (cities) and teams

- **Several sites** run almost as separate clubs: each member, team, bill and payment belongs to one city
- **Teams** with several **coaches and help-coaches**; coaches only see and manage their own teams
- Admins pick the site from the menu, or see all sites at once

### 📅 Meetings and attendance

- **Trainings and events** per team, one by one or as a weekly series
- **Big events**, and open events where members sign up themselves from the home page
- **Attendance tracking** by the coaches, with attendance rates on the dashboard

### 💰 Billing

- **Bills from templates**, created one by one or in batch for a whole team or season (membership fees)
- **Swiss QR bills** in PDF, sent by email or printed by post
- **Drafts and validation** before sending, reminders for late bills
- **Overview and search** of bills by season, team, status and label, with the list of members who did not get a bill yet
- Members see and download their own bills from their page

### 🧑‍🏫 Coach payments

- **Coach presence** declared per meeting (coach, help-coach, absent, ...), with an amount per presence type and per site
- **Balance per coach and per site**, payment with a QR code to the coach's IBAN, payment history
- Coaches see their balance and planned presences, and set their own IBAN (stored masked)
- Season statistics per presence type and per team

### 🛍️ Shop

- **Club shop** (uniforms, merchandising) with sizes or attributes and stock management
- **Orders turned into bills** automatically
- Merchandising managers can order for other members, follow delivery and see an order summary

### ♻️ Second-hand

- Members and past members **sell and buy used items** (uniforms, shoes) between themselves
- Categories, photos, seller profile with email confirmation, terms accepted once
- The club only connects buyers and sellers; the module can be turned off

### ✉️ Communication

- **Mailings** to the members of some teams, or to the members having a given bill
- Emails are queued and sent in small chunks, so a mailing stopped by the SMTP limit can be resumed
- **Contents** managed by the staff: home page messages, links and information per team
- Optional **Slack** messages on the home page

### 🏛️ Subsidies

- **Subsidy requests to the municipalities**: list of young members by city of residence, letter per city in PDF
- Ready-to-send emails (`.eml`) for every city, downloaded as one zip pack

### 📊 Administration

- **Dashboard**: members by gender and age, finances, attendance, teams and upcoming birthdays
- **New season** tools: new year, reset of memberships and registrations, generation of the membership fees
- **Roles** with fine-grained rights (view/edit all members, own members, bills, shop, ...)
- **Settings** for the club name, season, languages and texts
- Log viewer, maintenance mode and a style guide page for the interface

### 🌍 Languages and devices

- Interface in **French, German, English, Spanish and Italian**; each member chooses their language
- **Mobile friendly** everywhere, including iPhone photos (HEIC)

### 🔒 Security

- Role-based permissions checked on every page
- Rate limiting on logins and login links, security headers, CSRF protection
- Logs of suspicious requests and failed logins

See [docs/security.md](docs/security.md) for details.

## 🧰 Built with

[CakePHP 5.4](https://cakephp.org/) · PHP 8.4 · MySQL/MariaDB · [Bootstrap](https://getbootstrap.com/) with BootstrapUI · jQuery · [Swiss QR Bill](https://github.com/sprain/php-swiss-qr-bill) · TCPDF · Intervention Image · TinyMCE

## 📚 Documentation

- [INSTALL.md](INSTALL.md): installation, configuration, update, tests
- [CONTRIBUTING.md](CONTRIBUTING.md): how to contribute
- [docs/security.md](docs/security.md): security guide
- [docs/HEIC_SUPPORT.md](docs/HEIC_SUPPORT.md): iPhone photo support
- [docs/favicon-management.md](docs/favicon-management.md): favicons

## 🗺️ Roadmap

- [ ] Mobile application companion
- [ ] Advanced reporting and analytics
- [ ] Integration with external payment systems
- [ ] Enhanced notification system
- [ ] API documentation and external integrations

## 💬 Support

Report bugs and ask for features via [GitHub Issues](https://github.com/GrinwaldFlo/AngelAdmin2/issues).

## 👨‍💻 Authors

- **Florian Grinwald** - *Initial work* - [@GrinwaldFlo](https://github.com/GrinwaldFlo)

## 📄 License

MIT License, see [LICENSE](LICENSE).
