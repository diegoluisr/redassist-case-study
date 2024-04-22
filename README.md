# Micro Insurance - Case Study

Start with a clear headline. This should be like a newspaper headline that gives the most important information. ...
Provide a snapshot. ...
Introduce the client. ...
State the problem, consequences, & hesitations. ...
Describe the solution. ...
Share the results & benefits. ...
Conclude with words of advice and a CTA.

This case study is an overview for a project where I worked and lead almost 2 years ago, this project was a real challenge in the search of how to solve client needs using all the advantages of Drupal as a CMS/Framework.

This repository isn't an effort to show the complete workflow for the companies solutions, because they are covered by the bussiness process of them.

[933 Asistencia - B2C](https://933asistencia.com/) and [RedAssist](https://vivetranquilo.co/) are two different leader companies on the micro insurance for home issue in Colombia, they provide a wide pool of options to solve common issue in home as plumbery, broken glasses, locked doors, etc. Providing professionals to solve those issues.

Previous to this software solution, both companies use to engage new clients using paper contracts. So, this solution reduce the time for filling the forms, paper usage, and even take advantage of new laws to allow the digital process.

The process workflow was discussed and designed in colaboration with legal, product, sales and tech teams. I lead this process from the technical side and modeling the required process using BPMN.

![Diagram](./.project/assets/digital-signature-diagram.svg)
[Download](./.project/assets/digital-signature-diagram.bpmn) | [Open it on BMPN.io](https://bpmn.io/)

The process starts with a ensurance request, where the requester must to fill several fields, even including attaching support files in order to fulfil legal quality standards from the company and the govern, as an effort to avoid claims from problematic clients.

The process allow the companies to sale a larger number of insurances per day that regular. On the time lapse of 6 months, this software implementation sold more thah 2 thousand insurances, and aim the companies to accomplish their goals.

| B2C | RedAssist |
| --- | --------- |
| ![Diagram](./.project/assets/b2c.png) | ![Diagram](./.project/assets/redassist.png) |
| [Access form](https://ventas.b2c.net.co/sale/b2c/steps/427) | [Access form](https://ventas.b2c.net.co/virtual/redassist/miventa) |


Each request is considered an e-commerce sale, so, we create a client and its insurance as part of a commerce process.

When the cart is creted, an OTP is sent using a third party provider for OTP (One Time Password), this password is sent as SMS and audio call to validate insurance requester identity, I created `hablame` custom module to send SMS and start the call, and the `ffmpeg` module to create a custom audio concatenating gretting audio with numeric representations of digits.

## Custom Modules

Some custom modules are listed next to give more datail over the process specific needs.

### Hablame

This module communicates with hablame service to do some tasks related to create and send OTP and short URLs. [Hablame](https://www.hablame.co/) was selected as a local provider instead of more popular services as Twilio, because prices are lower to the target market (Colombia). Check the custom module created [here](./web/modules/custom/hablame/).

### FFMPEG

This module is a custom version of the contributed/sandboxed module with same name [drupal/ffmpeg](https://www.drupal.org/project/ffmpeg). [FFMPEG](https://ffmpeg.org/) was selected because it is an open source alternative to work with images and video. Check the custom module created [here](./web/modules/custom/ffmpeg/).

### Autentic

This module was created to connect to [Autentic](https://autenticlatam.com/) provider, it is a service to do digital signature legaly bindable, according to Colombian law. Check the custom module created [here](./web/modules/custom/autentic/).

### Gsuite

This module was created using the contributed/sandboxed module with same name [GSuite](https://www.drupal.org/project/gsuite), it was used to create spreadsheets reports of sales, asyncronically updated to bypass performace issues related to the large number of sales, and the limited resources of the backend server. Reports was shared on read-only mode to help sales team to check the daily/weekly/monthly numbers. Check the custom module created [here](./web/modules/custom/gsuite/).

### TIS (Telegram Integration Services)

This module was created to provide an alternative to sale insurances using Telegram API, this implementation exposes questions to requesters with the goal of fulfil all the needed fields in a regular form. Check the custom module created [here](./web/modules/custom/tis/).
