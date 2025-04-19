---
# https://vitepress.dev/reference/default-theme-home-page
layout: home

hero:
  name: "Laravel Wallet"
  text: It's simple!
  tagline: It's easy to work with a virtual wallet
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
    details: Atomic service for transactions. No need to worry about the state of the wallet.
    icon: 🔒
    link: /guide/db/atomic-service
  - title: Multi wallets
    details: Many wallets for one model. Easy API.
    icon: 💰
    link: /guide/main/multi-wallet
  - title: Events
    details: For more complex projects there are events and high performance API.
    icon: 📻
    link: /guide/events/wallet-updated-event
---