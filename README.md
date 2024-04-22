# Micro Insurance - Case Study

This case study is an overview for a project where I worked and lead almost 2 years ago, this project was a real challenge in the search of how to solve client needs using all the advantages of Drupal as a CMS/Framework

[933 Asistencia - B2C](https://933asistencia.com/) and [RedAssist](https://vivetranquilo.co/) are two different companies use a same code environment to sale micro-insurance for home issues.

The sale process was discussed and designed in colaboration with legal, product, sales and tech teams. I lead this process from the technical side and modeling the required process using BPMN.


![Diagram](./.project/assets/digital-signature-diagram.svg)
[Download](./.project/assets/digital-signature-diagram.bpmn) | [Open it at BMPN.io](https://bpmn.io/)

The process starts with a ensurance request, where the requester must to fill several fields, even including attaching support files in order to fulfil legal quality standards from the company and the govern, as an effort to avoid claims from problematic clients.

| B2C | RedAssist |
| --- | --------- |
| ![Diagram](./.project/assets/b2c.png) | ![Diagram](./.project/assets/redassist.png) |
| [Access form](https://ventas.b2c.net.co/sale/b2c/steps/427) | [Access form](https://ventas.b2c.net.co/virtual/redassist/miventa) |


Each request is considered an e-commerce sale, so, we create a client and its insurance as part of a commerce process.

When the cart is creted, an OPT is sent using a third party provider for OTP (One Time Password), this password is sent as SMS and audio call, I created `hablame` custom module to send SMS and start the call, and the `ffmpeg` module to create a custom audio concatenating gretting audio with numeric representations of digits.

[Hableme](https://www.hablame.co/) was selected as a local provider instead of more popular services as Twilio, because prices are lower to the target market (Colombia). Check the custom module created [here](./web/modules/custom/hablame/).

[FFMPEG](https://ffmpeg.org/) was selected because it is an open source alternative to work with images and video. Check the custom module created [here](./web/modules/custom/ffmpeg/).
