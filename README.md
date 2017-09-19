# TYPO3 Extension "webkitpdf"
This is a fork of webkitpdf with compatibility to TYPO3 6.2, 7.6 and 8.7. Furthermore there is rudimentary DoS protection added. With the TypoScript configuration plugin.tx_webkitpdf_pi1.numberOfUrlsAllowedToProcess the number of URLs per request is limited. By default it's 3. Set it to 0 to allow an unlimited number of URLs. But be aware that a attacker could pass so many URLs that the server breaks down.

Original documentation: https://github.com/DMKEBUSINESSGMBH/webkitpdf/blob/master/Documentation/Manual.pdf

**Caution since Version 2.0.0** The extension now uses namespaces and the TypoScript files have been moved from static/* to Configuration/TypoScript/* so make sure to adjust the paths or reinsert the static TypoScript.
