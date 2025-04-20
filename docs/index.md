---
layout: home

hero:
name: "Laravel Wallet"
text: Simple and powerful!
tagline: Effortlessly manage virtual wallets with elegance and control.
image:
src: https://github.com/user-attachments/assets/d45104c9-2388-4ed3-8386-6e7152029697
alt: Laravel Wallet
actions:
- theme: brand
  text: Getting Started
  link: /guide/introduction/
- theme: alt
  text: Upgrade Guide
  link: /guide/introduction/upgrade

features:
- title: Atomic Service
  details: Execute transactions atomically—no need to worry about inconsistent wallet states.
  icon: ⚙️
  link: /guide/db/atomic-service

- title: Consistency Checks
  details: Maintain wallet integrity with built-in consistency verification.
  icon: ✅
  link: /guide/main/multi-wallet

- title: Multi Wallets
  details: Effortlessly manage multiple wallets per model using a clean and intuitive API.
  icon: 👛
  link: /guide/main/multi-wallet

- title: Events
  details: Extend functionality with events and a high-performance architecture.
  icon: 📡
  link: /guide/events/wallet-updated-event

- title: Freezing Balances
  details: Temporarily freeze wallet balances without needing to withdraw and redeposit.
  icon: 🧊
  link: /guide/events/wallet-updated-event

- title: Fractional Numbers
  details: Seamlessly handle any numeric type or precision level.
  icon: 🔬
  link: /guide/events/wallet-updated-event
---