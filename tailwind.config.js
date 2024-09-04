/**
 * This is config file for tailwind.config.js
 *
 * Filename:        tailwind.config.php
 * Location:        /
 * Project:         SaaS-VANILLA-MVC
 * Date Created:    6/08/2024
 *
 * Author:          Sukhwan Ko <20111176@tafe.wa.edu.au>
 *
 */

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.{html,js}",
    "./{App,public,config,Framework}/**/*.{html,js,php}"
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
