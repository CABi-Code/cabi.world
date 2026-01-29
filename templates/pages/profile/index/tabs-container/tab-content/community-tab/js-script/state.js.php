const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
let currentCommunityId = <?= $community['id'] ?? 'null' ?>;
let currentParentId = null;
let currentItemType = null;
