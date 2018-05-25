plugin.tx_webkitpdf_pi1 {
    userFunc = DMK\Webkitpdf\Plugin->main
    pdfLink = TEXT
    pdfLink {
        value = {$plugin.tx_webkitpdf_pi1.pdfLink.linkText}
        typolink {
            parameter = {$plugin.tx_webkitpdf_pi1.pdfLink.pluginPid}
            additionalParams {
                data = getIndpEnv:TYPO3_REQUEST_URL
                rawUrlEncode = 1
                wrap = &tx_webkitpdf_pi1[urls][0]=|
            }
        }
    }

    ### mehr als 3 URLs können per default nicht verarbeitet werden.
    ### 0 heißt unbegrenzt. Nur in Ausnahmefällen, da der Server
    ### damit eine DoS Attacke auf sich selbst ausführen könnte
    ### indem eine große Anzahl von URLs übergeben wird.
    numberOfUrlsAllowedToProcess = 3
}

# Add HTTP response header "Content-Type" when serving PDF files
[globalVar = TSFE:id = {$plugin.tx_webkitpdf_pi1.pdfLink.pluginPid}]
config {
    ### notation for TYPO3 6.2
    additionalHeaders = Content-Type: application/pdf
    ### notation since TYPO3 7.6
    additionalHeaders {
        10.header = Content-Type: application/pdf
    }
}
[global]

