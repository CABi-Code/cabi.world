// ========== Состояние ==========
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
const userId = <?= $profileUser['id'] ?>;
const isOwner = <?= $isOwner ? 'true' : 'false' ?>;

let currentParentId = null;
let selectedItemType = null;
let currentEditItemId = null;
let draggedItem = null;
