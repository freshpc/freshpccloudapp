<?php
if (file_exists(__DIR__ . '/tcpdf/tcpdf.php')) {
    echo "TCPDF is installed and accessible!";
} else {
    echo "TCPDF not found!";
}
?>