---
# https://vitepress.dev/reference/default-theme-home-page
layout: home

hero:
  name: "Laravel Wallet"
  text: Simple and powerful!
  tagline: Effortlessly manage virtual wallets with ease.
  image:
    src: https://github.com/user-attachments/assets/d45104c9-2388-4ed3-8386-6e7152029697
    alt: Laravel Wallet
  actions:
    - theme: brand
      text: Getting started
      link: /guide/introduction/
    - theme: alt
      text: Upgrade Guide
      link: /guide/introduction/upgrade

features:
  - title: Atomic Service
    details: Perform transactions atomically—no need to worry about wallet state consistency.
    icon: 🔒
    link: /guide/db/atomic-service
  - title: Multi wallets
    details: Easily manage multiple wallets per model with a simple API.
    icon: 💰
    link: /guide/main/multi-wallet
  - title: Events
    details: Build complex features with events and a high-performance API.
    icon: 📻
    link: /guide/events/wallet-updated-event
---
