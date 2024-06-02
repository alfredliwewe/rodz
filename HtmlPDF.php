<?php
error_reporting(E_PARSE | E_ERROR);
/**
 * 
 */
class HtmlPDF
{
	
	public static function fromHtml($pdf,$html){
		$pdf->SetFont("Arial", '', 10);
		$pageWidth = $pdf->GetPageWidth() - 20;

		$dom = new DOMDocument();
		$dom->loadHTML($html);

		$body = $dom->getElementsByTagName("body")->item(0);

		foreach (iterator_to_array($body->childNodes) as $node) {
			if ($node instanceof DOMElement) {
				$styles = [];
				if ($node->getAttribute("style") != null) {
					$chars = explode(";", $node->getAttribute("style"));
					foreach ($chars as $char) {
						$vars = explode(":", $char);
						if (count($vars) == 2) {
							$styles[trim($vars[0])] = Strings::trim($vars[1],6);
						}
					}
				}

				if ($node->tagName == "div" OR $node->tagName == "p") {
					if ($node->hasChildNodes()) {
						$pdf->SetFont("Arial", isset($styles['font-weight']) ? 'B':'', isset($styles['font-size']) ? str_replace("px", "", $styles['font-size']) : 9);
						if (isset($styles['text-align'])) {
							$pdf->Cell(null,6,$node->nodeValue,0,0,'C');
						}
						else{
							$pdf->Cell(null,6,$node->nodeValue);
						}
					}
					else{
						$pdf->SetFont("Arial", isset($styles['font-weight']) ? 'B':'', isset($styles['font-size']) ? str_replace("px", "", $styles['font-size']) : 9);
						$pdf->Cell(null,6,$node->nodeValue);
					}
					$pdf->Ln();
				}
				elseif ($node->tagName == "table") {
					$widths = [];
					$hasBorders = $node->hasAttribute("border");
					$auto = true;

					$firstRow = $node->getElementsByTagName("tr")->item(0);
					if ($firstRow != null) {
						$tds = $firstRow->getElementsByTagName("td");
						foreach ($tds as $td) {
							$hasWidth = false;
							if ($td->hasAttribute("style")) {
								$td_styles = self::get_styles($td->getAttribute("style"));
								if (isset($td_styles['width'])) {
									array_push($widths, (Strings::getNumber($td_styles['width'])/100)*$pageWidth);
									$hasWidth = true;
									$auto = false;
								}
							}

							if (!$hasWidth) {
								array_push($widths, (1/$tds->length)*$pageWidth);
							}
						}
						if ($auto) {
							$widths = self::getDynamicWidths($node, $pageWidth);
						}

						foreach ($node->getElementsByTagName("tr") as $tr) {
							$tr_styles = [];
							$fill = false;

							if ($tr->hasAttribute("style")) {
								$tr_styles = self::get_styles($tr->getAttribute("style"));
								if (isset($tr_styles['font-weight'])) {
									if ($tr_styles['font-weight'] == "bold") {
										$pdf->SetFont("Arial", 'B', 9);
									}
								}

								//for background  -- only rgb supported
								if (isset($tr_styles['background-color'])) {
									$fill = true;

									$rgb = str_replace(" ","", trim(Strings::cutBetween($tr_styles['background-color'], "(",")")));
									$ints = explode(",", $rgb);
									$pdf->SetFillColor((int)$ints[0], (int)$ints[1], (int)$ints[2]);
									//$pdf->SetFillColor(230, 230, 230);
								}
							}

							$k = 0;
							foreach ($tr->getElementsByTagName("td") as $td) {
								$pdf->Cell($widths[$k],7,$td->nodeValue,$hasBorders?1:0,0,'L',$fill);
								$k += 1;
							}
							$pdf->Ln();
							$pdf->SetFont("Arial", '', 9);
						}
					}
				}
				elseif ($node->tagName == "hr") {
					$pdf->Cell(null,2,'','B');
					$pdf->Ln();
				}
				elseif ($node->tagName == "br") {
					$pdf->Ln();
				}
				elseif ($node->tagName == "img") {
					$filename = $node->getAttribute("src");
					$width = $node->getAttribute("width");

					//calculate image height
					list($img_width,$img_height) = getimagesize($filename);
					$height = $width/$img_width * $img_height;
					$pdf->Image($filename, null, null, $width,$height);
					$pdf->Ln();
				}
			}

			$pdf->SetFont("Arial", '', 9);
		}
	}

	public static function get_styles($text)
	{
		$styles = [];
		$chars = explode(";", $text);
		foreach ($chars as $char) {
			$vars = explode(":", $char);
			if (count($vars) == 2) {
				$styles[trim($vars[0])] = $vars[1];
			}
		}

		return $styles;
	}

	public static function getDynamicWidths($node, $pageWidth){
		$rows = [];
		foreach($node->getElementsByTagName("tr") as $tr){
			$col = [];
			foreach ($tr->getElementsByTagName("td") as $td) {
				array_push($col, $td->nodeValue);
			}
			array_push($rows, $col);
		}

		$longest = array_values(self::findLongestInEachColumn($rows));
		
		for ($i=0; $i < count($longest); $i++) { 
			$longest[$i] = $longest[$i] < 4 ? 4 : $longest[$i];
		}
		$total = array_sum($longest);
		$widths = [];
		foreach ($longest as $value) {
			$width = $value / $total * $pageWidth;
			array_push($widths, $width);
		}

		return $widths;
	}

	public static function findLongestInEachColumn($table) {
	    $longest_lengths = [];

	    foreach ($table as $row) {
	        foreach ($row as $col_index => $cell) {
	            if (!isset($longest_lengths[$col_index])) {
	                $longest_lengths[$col_index] = 0;
	            }
	            $longest_lengths[$col_index] = max($longest_lengths[$col_index], strlen($cell));
	        }
	    }

	    return $longest_lengths;
	}
}
?>