import './styles/df-select.css'

const dom = (strings, ...values) => {
	const rawHtml = String.raw({ raw: strings }, ...values);
	const parser = new DOMParser();
	const parsedHtml = parser.parseFromString(rawHtml, "text/html");
	return parsedHtml.body.firstChild;
}

function initialize(select) {
	const dfSelect = dom`<div class="df-select"></div>`;
	const parent = select.parentElement;

	select.classList.forEach((classItem) => dfSelect.classList.add(classItem));

	parent.insertBefore(dfSelect, select);
	dfSelect.appendChild(select);
	select.style.display = "none";

	const dfSelectBox = dom`<button type="button" class="df-select-box"></button>`;
	dfSelect.appendChild(dfSelectBox);

	const dfSelectMenu = dom`<div class="df-select-menu"></div>`;
	dfSelect.appendChild(dfSelectMenu);

	return { dfSelect: dfSelect, dfSelectBox: dfSelectBox, dfSelectMenu: dfSelectMenu };
}

function modifySingleSelect(select) {
	if (true === select.hasAttribute("multiple")) {
		return;
	}
	const { dfSelect, dfSelectBox, dfSelectMenu } = initialize(select);

	for (const item of select.children) {
		const value = item.getAttribute("value") || "";
		const view = item.innerText || "";
		const selected = item.selected;
		const dfSelectMenuItem = dom`<button type="button" class="df-select-menu-item" data-df-value="${value}"${true === selected ? 'data-df-selected' : ''}>${view}</button>`;

		if (true === selected) {
			const dfSelectedItem = createSelectedItemElement(select, dfSelectMenu, value, view);
			dfSelectBox.replaceChildren(dfSelectedItem);
		}

		dfSelectMenuItem.addEventListener("click", function () {
			if (!"dfValue" in this.dataset) {
				return;
			}

			select.value = this.dataset.dfValue;
			dfSelectMenu.querySelectorAll("[data-df-selected]").forEach((elem) => elem.removeAttribute("data-df-selected"));
			this.dataset.dfSelected = "";

			const dfSelectedItem = createSelectedItemElement(select, dfSelectMenu, this.dataset.dfValue, this.innerText);
			dfSelectBox.replaceChildren(dfSelectedItem);
		});

		dfSelectMenu.appendChild(dfSelectMenuItem)
	}

	function createSelectedItemElement(select, dfSelectMenu, value, text) {
		const dfSelectedItem = dom`
			<div class="df-selected-item" data-df-value="${value}">
				<span>${text}</span>
				<button type="button">&times;</button>
			</div>`
		;

		dfSelectedItem.querySelector("button").addEventListener("click", function () {
			if ("dfValue" in dfSelectedItem.dataset) {
				const option = select.querySelector(`option[value="${dfSelectedItem.dataset.dfValue}"]`);
				if (null !== option) option.selected = false;
				const menuItem = dfSelectMenu.querySelector(`[data-df-selected][data-df-value="${dfSelectedItem.dataset.dfValue}"]`);
				if (null !== menuItem) menuItem.removeAttribute("data-df-selected");
			}
			dfSelectedItem.remove();
		});

		return dfSelectedItem;
	}
}

function modifyMultipleSelect(select) {
	if (false === select.hasAttribute("multiple")) {
		return;
	}
	const { dfSelect, dfSelectBox, dfSelectMenu } = initialize(select);

	for (const item of select.children) {
		const value = item.getAttribute("value") || "";
		const view = item.innerText || "";
		const selected = item.selected;
		
		const dfSelectMenuItem = dom`<button type="button" class="df-select-menu-item" data-df-value="${value}"${true === selected ? 'data-df-selected' : ''}>${view}</button>`;
		dfSelectMenuItem.dfBinding = { option: item, view: null };
		dfSelectMenu.appendChild(dfSelectMenuItem)

		if (true === selected) {
			const dfSelectedItem = createSelectedItemElement(dfSelectMenuItem, value, view)
			dfSelectBox.appendChild(dfSelectedItem);
		}

		dfSelectMenuItem.addEventListener("click", function () {
			const context = this;

			if (!"dfValue" in context.dataset) {
				return;
			}
	
			if (false === context.dfBinding.option.selected) {
				context.dfBinding.option.selected = true;
				context.dataset.dfSelected = "";
				const dfSelectedItem = createSelectedItemElement(dfSelectMenuItem, context.dataset.dfValue, context.innerText)
				dfSelectBox.appendChild(dfSelectedItem);
			} else {
				context.dfBinding.option.selected = false;
				context.removeAttribute("data-df-selected");
				if (null !== context.dfBinding.view) {
					context.dfBinding.view.remove();
					context.dfBinding.view = null;
				}
			}
		});
	}

	function createSelectedItemElement(dfSelectMenuItem, value, text) {
		const dfSelectedItem = dom`
			<div class="df-selected-item" data-df-value="${value}">
				<span>${text}</span>
				<button type="button">&times;</button>
			</div>`
		;
		dfSelectedItem.dfBinding = { menuItem: dfSelectMenuItem };
		dfSelectedItem.querySelector("button").addEventListener("click", function () {
			dfSelectMenuItem.dfBinding.option.selected = false;
			dfSelectMenuItem.removeAttribute("data-df-selected");
			dfSelectMenuItem.dfBinding.view = null;
			dfSelectedItem.remove();
		});
		dfSelectMenuItem.dfBinding.view = dfSelectedItem;
		return dfSelectedItem;
	}
}

window.addEventListener("DOMContentLoaded", () => {
	document.querySelectorAll("select[data-df-select]:not([multiple])").forEach((select) => modifySingleSelect(select));
	document.querySelectorAll("select[data-df-select][multiple]").forEach((select) => modifyMultipleSelect(select));
});