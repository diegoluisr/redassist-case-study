# Micro Insurance - Case Study

This case study is an overview of a project I worked on and led almost two years ago. This project was a real challenge in the search for how to solve client needs using all the advantages of Drupal as a CMS/Framework.

This repository doesn't attempt to show the complete workflow for the company's solutions because their business processes cover them.

[933 Asistencia - B2C](https://933asistencia.com/) and [RedAssist](https://vivetranquilo.co/) are two leading companies in Colombia that provide micro-insurance for home issues. They offer a wide range of options to solve common home issues, such as plumbing, broken glasses, locked doors, etc., by hiring professionals to solve those issues.

Before this software solution, both companies used paper contracts to engage new clients. So, this solution reduces the time needed to fill out the forms and paper usage and even takes advantage of new laws to allow the digital process.

The process workflow was discussed and designed in collaboration with the legal, product, sales, and tech teams. I led this process from the technical side and modeled the required process using BPMN.

![Diagram](./.project/assets/digital-signature-diagram.svg)
[Download](./.project/assets/digital-signature-diagram.bpmn) | [Open it on BMPN.io](https://bpmn.io/)

The process starts with an insurance request, where the requester must fill in several fields, including attaching support files to fulfill legal quality standards from the company and the government to avoid claims from problematic clients.

The process allows the companies to sell more insurance daily than usual. In the time lapse of 6 months, this software implementation sold more than 2 thousand insurance policies, aiming to accomplish the companies' goals.

| B2C | RedAssist |
| --- | --------- |
| ![Diagram](./.project/assets/b2c.png) | ![Diagram](./.project/assets/redassist.png) |
| [Access form](https://ventas.b2c.net.co/sale/b2c/steps/427) | [Access form](https://ventas.b2c.net.co/virtual/redassist/miventa) |

Each request is considered an e-commerce sale, so we create a client and its insurance as part of a commerce process.

When the cart is created, an OTP (one-time password) is sent using a third-party provider. This password is sent as an SMS and audio call to validate the insurance requester's identity. I created a `hablame` custom module to send SMS and start the call and the `ffmpeg` module to create a custom audio concatenating audio with numeric representations of digits.

## Custom Modules

Some custom modules are listed next to give more detail about the process-specific needs.

### Hablame

This module communicates with the Hablame service to perform some tasks related to creating and sending OTP and short URLs. [Hablame](https://www.hablame.co/) was selected as a local provider instead of more popular services like Twilio because its prices are lower in the target market (Colombia). Check the custom module created [here](./web/modules/custom/hablame/).

### FFMPEG

This module is a custom version of the contributed/sandboxed module with the same name: [drupal/ffmpeg](https://www.drupal.org/project/ffmpeg). [FFMPEG](https://ffmpeg.org/) was selected because it is an open-source alternative for working with images and video. Check the custom module created [here](./web/modules/custom/ffmpeg/).

### Autentic

I created this module to connect to [Autentic](https://autenticlatam.com/) a service that provides legally bindable digital signatures according to Colombian law. Check the custom module created [here](./web/modules/custom/autentic/).

### Gsuite

The Gsuite module, a solution-oriented creation, was developed using the contributed/sandboxed module with the same name [GSuite](https://www.drupal.org/project/gsuite), It was specifically designed to create spreadsheet reports of sales, which are asynchronously updated to overcome performance issues related to the large number of sales and the limited resources of the backend server. These reports are shared in read-only mode, facilitating the sales team in monitoring the daily/weekly/monthly numbers. Discover this problem-solving custom module [here](./web/modules/custom/gsuite/).

### TIS (Telegram Integration Services)

This module was created to provide an alternative to selling insurance using Telegram API. This implementation exposes questions to requesters with the goal of fulfilling all the needed fields in a regular form. Check the custom module created [here](./web/modules/custom/tis/).
