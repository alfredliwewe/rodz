HtmlPDF class helps you convert html into pdf, while using FPDF in php

# How to do it

First initialize the FPDF

```php
$pdf = new FPDF();
$pdf->AddPage();

$html = "<body>
  <div>Most Sold by Qty</div>

		<br/>

		<table>
			<tr style=\"font-weight:bold;background-color: rgb(153, 194, 255);\">
				<td>#</td>
				<td>Menu Item</td>
				<td>Description</td>
				<td>Qty</td>
				<td>Total</td>
				<td>Total Net</td>
				<td>Total VAT</td>
			</tr>
			<tbody></tbody>
    </table>
</body>";

# then load the html into the pdf by
HtmlPDF::fromHtml($pdf, $html);

$pdf->Output();
```

Please take not that limited html and CSS are supported
