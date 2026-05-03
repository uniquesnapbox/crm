<?php
$root = __DIR__;
function filesIn($dirs, $exts){
  $all=[];
  foreach($dirs as $d){
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS));
    foreach($it as $f){
      if(!$f->isFile()) continue;
      foreach($exts as $e){ if(str_ends_with($f->getFilename(), $e)){ $all[]=$f->getPathname(); break; } }
    }
  }
  return $all;
}
$viewFiles = filesIn(["$root/resources/views"], ['.blade.php']);
$views=[];
foreach($viewFiles as $vf){
  $rel = str_replace('\\','/',substr($vf, strlen("$root/resources/views/") ));
  $name = preg_replace('/\.blade\.php$/','',$rel);
  $name = str_replace('/','.',$name);
  $views[$name] = $vf;
}
$codeFiles = filesIn(["$root/app","$root/routes","$root/resources/views"], ['.php','.blade.php']);
$patterns = [
  "/view\\(\\s*['\"]([^'\"]+)['\"]/",
  "/@include(?:If|When|First)?\\(\\s*['\"]([^'\"]+)['\"]/",
  "/@extends\\(\\s*['\"]([^'\"]+)['\"]/",
  "/@component\\(\\s*['\"]([^'\"]+)['\"]/",
  "/->view\\(\\s*['\"]([^'\"]+)['\"]/"
];
$refs=[];
foreach($codeFiles as $cf){
  $txt = @file_get_contents($cf);
  if($txt===false) continue;
  foreach($patterns as $p){
    if(preg_match_all($p,$txt,$m)){
      foreach($m[1] as $val){
        if(str_contains($val,'::')) continue;
        $val = str_replace('/','.',$val);
        $refs[$val]=true;
      }
    }
  }
}
$unref=[];
foreach($views as $name=>$path){
  if(!isset($refs[$name])) $unref[] = [$name,$path];
}
usort($unref, fn($a,$b)=>strcmp($a[0],$b[0]));
file_put_contents("$root/tmp_unreferenced_views_static.txt", implode("\n", array_map(fn($x)=>$x[0]."|".$x[1], $unref)));
echo "TOTAL_VIEWS=".count($views)."\n";
echo "TOTAL_REFS=".count($refs)."\n";
echo "TOTAL_UNREF=".count($unref)."\n";
