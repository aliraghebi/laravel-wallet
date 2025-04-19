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
  - title: Default Wallet
    details: For simple projects when there is no need for multiple wallets.
    icon: 💰
    link: /guide/single/deposit
  - title: Multi wallets
    details: Many wallets for one model. Easy API.
    icon: 🎒
    link: /guide/multi/new-wallet
   - title: Events
    details: For more complex projects there are events and high performance API.
    icon: 📻
    link: /guide/events/balance-updated-event
---

