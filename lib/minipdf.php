<?php
// Minimal text-only PDF for reports
class MiniPDF {
    protected $pages=[]; protected $w=210; protected $h=297; protected $size=12; protected $y=20; protected $x=10;
    function AddPage(){ $this->pages[]=''; $this->y=20; $this->x=10; }
    function SetTitle($t){ $this->title=$t; }
    function SetAuthor($a){ $this->author=$a; }
    function SetFont($f,$s=12){ $this->size=$s; }
    function Ln($h=null){ $this->y += $h?:6; $this->x = 10; }
    function Cell($w,$h,$txt=''){ $this->pages[count($this->pages)-1] .= sprintf("BT /F1 %d Tf %.2f %.2f Td (%s) Tj ET\n", $this->size, $this->x*2.83465, ($this->h-$this->y)*2.83465, $this->esc($txt)); $this->y += $h; }
    function MultiCell($w,$h,$txt=''){ foreach(explode("\n",wordwrap($txt,90)) as $line){ $this->Cell($w,$h,$line); } }
    function esc($s){ return str_replace(['\\','(',')',"\r"],['\\\\','\\(','\\)', ''],$s); }
    function Output($dest='S',$name='report.pdf'){
        $out="%PDF-1.3\n"; $objs=[]; $n=1;
        $font=$n++; $objs[$font]="<< /Type /Font /Subtype /Type1 /Name /F1 /BaseFont /Helvetica >>";
        $pages=$n++; $kids=[]; $content=[];
        foreach($this->pages as $p){ $cnum=$n++; $content[$cnum]="<< /Length ".strlen($p)." >>\nstream\n".$p."endstream"; $pnum=$n++; $kids[]="$pnum 0 R"; $objs[$pnum]="<< /Type /Page /Parent $pages 0 R /Resources << /Font << /F1 $font 0 R >> >> /MediaBox [0 0 ".($this->w*2.83465)." ".($this->h*2.83465)."] /Contents $cnum 0 R >>"; }
        $objs[$pages]="<< /Type /Pages /Count ".count($this->pages)." /Kids [ ".implode(' ',$kids)." ] >>";
        $info=$n++; $objs[$info]="<< /Title (".$this->esc($this->title??'').") /Author (".$this->esc($this->author??'').") >>";
        $root=$n++; $objs[$root]="<< /Type /Catalog /Pages $pages 0 R >>";
        $ofs=[]; foreach(range(1,$root) as $i){ $obj = $content[$i] ?? ($objs[$i] ?? null); if(!$obj) continue; $ofs[$i]=strlen($out); $out.="$i 0 obj\n$obj\nendobj\n"; }
        $xref=strlen($out); $out.="xref\n0 ".($root+1)."\n0000000000 65535 f \n"; for($i=1;$i<=$root;$i++){ $off=$ofs[$i]??0; $out.=sprintf("%010d 00000 n \n",$off); }
        $out.="trailer << /Size ".($root+1)." /Root $root 0 R /Info $info 0 R >>\nstartxref\n$xref\n%%EOF";
        if($dest==='S') return $out; header('Content-Type: application/pdf'); header('Content-Disposition: inline; filename="'.$name.'"'); echo $out;
    }
}