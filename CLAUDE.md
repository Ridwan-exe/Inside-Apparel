# CLAUDE.md

# INSIDE APPAREL

## PROJECT

Project Name:
Inside Apparel

Project Type:
Professional E-Commerce Platform

Primary Stack:
- WordPress
- WooCommerce
- PHP
- MySQL
- JavaScript
- HTML
- CSS
- REST API

---

# DEVELOPMENT RULES

## 1. WordPress Core

DO NOT modify WordPress core files.

Custom functionality must never be placed directly inside WordPress core.

---

## 2. WooCommerce Core

DO NOT modify WooCommerce core files.

Custom WooCommerce functionality must use:

- hooks
- filters
- actions
- REST API
- custom plugins
- supported WooCommerce extension mechanisms

---

## 3. Inside Apparel Custom Code

Business logic specific to Inside Apparel should primarily be placed inside:

wp-content/plugins/inside-apparel-core/

Frontend presentation should primarily be placed inside:

wp-content/themes/inside-apparel/

---

## 4. Database

Before creating a custom database table:

1. Check whether WordPress already provides suitable storage.
2. Check whether WooCommerce already provides suitable storage.
3. Only create a custom table when there is a clear requirement.

Database changes must be documented.

---

## 5. Security

NEVER:

- hardcode passwords
- hardcode API keys
- hardcode secret tokens
- expose credentials
- commit production credentials
- commit .env files containing secrets

Always:

- validate input
- sanitize input
- escape output
- use WordPress APIs
- check user capabilities
- use nonce verification where required

---

## 6. Coding Principles

Prioritize:

1. Security
2. Reliability
3. Maintainability
4. Performance
5. Simplicity

Avoid unnecessary complexity.

Do not over-engineer.

Build the simplest reliable solution that satisfies the requirement.

---

## 7. Plugins

Do not install additional plugins without approval from the project owner.

Before recommending a plugin, explain:

- why it is required
- what problem it solves
- whether custom development could replace it
- security impact
- performance impact
- maintenance impact

Keep the number of plugins as low as reasonably possible.

---

## 8. Existing Functionality

Never delete or replace existing functionality without approval.

For destructive changes:

1. Explain the change.
2. Explain why it is required.
3. Explain possible consequences.
4. Request approval.

---

# INVENTORY

## Central Inventory

Inside Apparel should use a central inventory/source-of-truth concept.

Target:

```text
                 CENTRAL INVENTORY
                        │
              ┌─────────┴─────────┐
              │                   │
           WEBSITE              SHOPEE