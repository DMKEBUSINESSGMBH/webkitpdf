# TYPO3 Extension "webkitpdf"
This is a fork of webkitpdf with compatibility to TYPO3 6.2. Furthermore there is rudimentary DoS protection added. With the TYpoScript configuration plugin.tx_webkitpdf_pi1.numberOfUrlsAllowedToProcess the number of URLs per request is limited. By default it's 3. Set it to 0 to allow an unlimited number of URLs. But be aware that a attacker could pass so many URLs that the server breaks down.

Original documentation: https://github.com/DMKEBUSINESSGMBH/webkitpdf/blob/master/doc/manual.pdf