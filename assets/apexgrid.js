import "igniteui-webcomponents/themes/light/bootstrap.css";
import "./styles/apexgrid.override.css";

import { ApexGrid } from "apex-grid";
ApexGrid.register();

function createApexLinkColumnDef(columnDef) {
    return {
        key: columnDef.key,
        sort: columnDef.sort,
        filter: columnDef.filter,
        cellTemplate: (params) => {
            const link = document.createElement("A");
            link.setAttribute("href", params.value.url);
            link.innerText = params.value.value;
            return link;
        }
    };
}

function createApexNormalColumnDef(columnDef) {
    return {
        key: columnDef.key,
        sort: columnDef.sort,
        filter: columnDef.filter
    };
}

function generateColumnDefs(grid) {
    if ("columnDefs" in grid.dataset) {
        grid.columns = [];
        const columnDefs = JSON.parse(grid.dataset.columnDefs);
        for (const columnDef of columnDefs) {
            if (columnDef.type === "link") {
                grid.columns.push(createApexLinkColumnDef(columnDef));
            } else {
                grid.columns.push(createApexNormalColumnDef(columnDef));
            }
        }
        delete grid.dataset.columnDefs;
    }
}

window.addEventListener("load", () => {
    const grids = document.getElementsByTagName("apex-grid");

    for (const grid of grids) {
        generateColumnDefs(grid);
    }
});