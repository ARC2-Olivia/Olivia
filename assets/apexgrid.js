import "igniteui-webcomponents/themes/light/bootstrap.css";
import "./styles/apexgrid.override.css";

import { ApexGrid } from "apex-grid";
import axios from "axios";
ApexGrid.register();

function createApexLinkColumnDef(columnDef) {
    return {
        key: columnDef.key,
        sort: columnDef.sort || false,
        filter: columnDef.filter || false,
        cellTemplate: (params) => {
            const parser = new DOMParser();
            return parser.parseFromString(params.value, "text/html").body;
        }
    };
}

function createApexNormalColumnDef(columnDef) {
    return {
        key: columnDef.key,
        sort: columnDef.sort || false,
        filter: columnDef.filter || false
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

function fillWithData(grid) {
    if ("url" in grid.dataset) {
        axios({
            method: "GET",
            url: grid.dataset.url,
            responseType: "json",
        }).then(function (response) {
            const dataFromResponse = response.data;
            if (dataFromResponse.status === 'success') {
                grid.data = dataFromResponse.data;
            }
        });
    }
}

window.addEventListener("load", () => {
    const grids = document.getElementsByTagName("apex-grid");

    for (const grid of grids) {
        generateColumnDefs(grid);
        fillWithData(grid);
    }
});