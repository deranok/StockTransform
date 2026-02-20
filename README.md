# StockTransform Module FOR Dolibarr

## Features

Easily change some units of Product A to Product B.
Example, change 1 Bottle of Product A plus 2 Bottles of Product B to one cup of Product C.

## Installation

Prerequisites: You must have Dolibarr ERP & CRM software installed. You can download it from [Dolistore.org](https://www.dolibarr.org).
You can also get a ready-to-use instance in the cloud from https://saas.dolibarr.org

### From a GIT repository

Clone the repository in `$dolibarr_main_document_root_alt/mymodule`

```shell
cd ....../custom
git clone git@github.com:gitlogin/mymodule.git mymodule
```

### Final steps

Using your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup"> "Modules"
  - You should now be able to find and enable the module

## Usage
Once module is installed, it would show up in the left menu under products. You add the products to transform from and then the product to transform to. Then you enter the description for the movement. That's it.

## Licenses

### Main code

GPLv3 or (at your option) any later version. See file COPYING for more information.

### Documentation

All texts and readme's are licensed under [GFDL](https://www.gnu.org/licenses/fdl-1.3.en.html).
