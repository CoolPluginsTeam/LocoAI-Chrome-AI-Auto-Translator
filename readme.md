# 🌐 LocoAI – Chrome AI Auto Translator

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/CoolPluginsTeam/locoai-chrome-ai-auto-translator/)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.2%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Built with Chrome AI](https://img.shields.io/badge/Built%20with-Chrome%20Gemini%20Nano-orange.svg)](https://developer.chrome.com/docs/ai)

> **LocoAI – Chrome AI Auto Translator** is a Chrome Extension and WordPress companion tool that uses **Google Chrome’s built-in AI Translator API (Gemini Nano)** to automatically translate plugin and theme strings directly in the browser — no paid API keys or external services required.

---

## 📘 Table of Contents

- [Overview](#overview)
- [Problem Statement](#problem-statement)
- [Solution](#solution)
- [Key Features](#key-features)
- [Tech Stack & APIs Used](#tech-stack--apis-used)
- [Installation](#installation)
- [Usage](#usage)
- [Demo Video](#demo-video)
- [Open Source Repository](#open-source-repository)
- [Future Enhancements](#future-enhancements)
- [Support](#support)
- [License](#license)
- [Team](#team)
- [Links](#links)

---

## 🧠 Overview

**LocoAI – Chrome AI Auto Translator** empowers WordPress developers and site owners to instantly translate any plugin or theme strings using **Chrome’s built-in Translator API**, powered by **Gemini Nano**.  
It works as a **browser-side Chrome Extension** that communicates with WordPress’s Loco Translate UI, enabling one-click translation of all language strings directly in the browser — **fast, private, and cost-free**.

This project is part of the **Google Chrome Built-in AI Challenge 2025**, under the *Chrome Extension – Most Helpful Category*.

---

## ❓ Problem Statement

Translating WordPress plugins and themes manually or through paid translation services (e.g., DeepL, Google Translate API) can be expensive and time-consuming.  
Existing translation plugins require:
- API keys from third-party providers  
- Usage-based pricing  
- Privacy risks from sending text externally  

---

## 💡 Solution

**LocoAI** leverages **Chrome’s built-in AI Translator API**, which runs locally via **Gemini Nano**, allowing:
- Secure and private in-browser translations  
- No API key or cloud dependency  
- Instant results (up to 25 000 characters/minute)  
- Seamless integration with WordPress Loco Translate  

---

## 🚀 Key Features

✅ **One-click Translation** – Instantly translate all strings within the Loco Translate editor.  
✅ **Unlimited Translations** – No usage caps or API costs.  
✅ **AI Quality Translation** – Uses the Chrome Translator API for high-accuracy results.  
✅ **Privacy First** – All processing happens locally using Gemini Nano.  
✅ **Developer-Friendly** – Open-source code and easy customization.  
✅ **Cross-Platform** – Works with any WordPress plugin or theme supporting `.po` files.

---

## 🧩 Tech Stack & APIs Used

**Platform:** Chrome Extension + WordPress Plugin  
**Primary AI APIs:**
- [Chrome Translator API](https://developer.chrome.com/docs/ai/translator-api)  
- [Prompt API for Chrome Extensions](https://developer.chrome.com/docs/ai/prompt-api)

**Other Technologies:**
- WordPress 5.0+ / PHP 7.2+  
- JavaScript (ES6), HTML, CSS  
- Chrome Extension Manifest V3  

---

## 🛠️ Installation

### For WordPress Plugin
1. Download the repository or clone it:
   ```bash
   git clone https://github.com/CoolPluginsTeam/locoai-chrome-ai-auto-translator.git
