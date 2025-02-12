<?php
/* METADATA */
$metaData = (object) [
  'siteTitle' => $site->title()->value(),
  'title' => $page->pageTitle()->isNotEmpty() ? $page->pageTitle()->value() : $site->pageTitle()->value(),
  'description' => $page->description()->isNotEmpty() ? $page->description()->value() : $site->description()->value(),
  'keywords' => $page->keywords()->isNotEmpty() ? $page->keywords()->value() : $site->keywords()->value(),
  'image' => $page->ogImage()->isNotEmpty() ? $page->ogImage()->toFile()?->url() : $site->ogImage()->toFile()?->url(),
];

/* INTRO */
if ($page->services()->isNotEmpty()) {
  foreach ($page->services()->toStructure() as $key=>$item) {
    $servicesList[] = (object) [
      'key' => $key,
      'icon' => $item->icon()->value(),
      'title' => $item->title()->kti()->typograf(),
      'text' => $item->text()->kti()->typograf(),
    ];
  }
}
$intro = (object) [
  'services' => $servicesList ?? null,
  'text' => $page->introText()?->kti()?->typograf() ?: null,
];

/* CLIENTS */
if ($page->clientsList()->isNotEmpty()) {
  foreach ($page->clientsList()->toStructure() as $key=>$item) {
    $clientsList[] = $item->name()->value();
  }
}
$clients = (object) [
  'text' => $page->clientsText()?->kti()?->typograf() ?: null,
  'list' => $clientsList ?? null,
];

/* AWARDS */
if ($page->awardsList()->isNotEmpty()) {
  foreach ($page->awardsList()->toStructure() as $key=>$item) {
    $awardsList[] = (object) [
      'key' => $key,
      'project' => $item->project()->value(),
      'award' => $item->award()->value(),
      'year' => $item->year()->value(),
      'prize' => $item->prize()->value(),
      'link' => $item->link()?->value() ?: null,
    ];
  }
}
$awards = (object) [
  'list' => $awardsList ?? null,
];

/* TECHNOLOGIES */
if ($page->technologiesList()->isNotEmpty()) {
  foreach ($page->technologiesList()->toStructure() as $key=>$item) {
    $technologiesList[] = (object) [
      'key' => $key,
      'title' => $item->title()->value(),
      'list' => $item->list()?->split() ?: null,
    ];
  }
}
$technologies = (object) [
  'list' => $technologiesList ?? null,
];

$pageData = (object) [
  'templateName' => $page->template()->name(),
  'seo' => $metaData,
  'intro' => $intro,
  'clients' => $clients,
  'awards' => $awards,
  'technologies' => $technologies,
];

$kirby->response()->json();
echo json_encode($pageData);
