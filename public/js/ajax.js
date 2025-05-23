/**
 * @copyright 2019 Create By Feng Chi-En
 * 
 * All proccessing about API
 */
$.fn.dataTable.ext.errMode = 'throw'; //for datatable throw error message, not alert interrupt.

var gnAjax = function (api_url) {
    if(!api_url) {
        this.web_api_dns = window.location.protocol + "//" + window.location.host + "/api/v1";
    } else {
        this.web_api_dns = api_url;
    }
    this.auth_name   = "X-Token";
    this.isTokening  = false;
    this.ajaxHolder  = [];
    this.g_x_token   = {};
    
    this.ajaxOpt = function (method, url, callback) {
        return {
            method: method,
            type:   method,
            url:    url,
            headers:this.g_x_token,
            cache:  false,
            dataType: "json", // with json response
            processData: true,
            beforeSend: function (XMLHttpRequest) {
                if( "beforesend" in callback ) {
                    callback.beforesend( XMLHttpRequest );
                }
            },
            success: function ( data_json, textStatus, jqXHR ) {
                if( "success" in callback ) {
                    callback.success( data_json, textStatus, jqXHR );
                }
            },
            error: function ( jqXHR, textStatus, errorThrown ) {
                if ("error" in callback) {
                    callback.error( jqXHR, textStatus, errorThrown );
                }
            },
            complete: function ( jqXHR, textStatus ) {
                if( "complete" in callback ) {
                    callback.complete( jqXHR, textStatus );
                }
            }
        }
    };
    
    this.getAJAX = function ( url, callback ) {
        this.apiSubmit( this.ajaxOpt( "GET", this.web_api_dns + url, callback ) );
    };
    
    this.delAJAX = function ( url, callback ) {
        this.apiSubmit( this.ajaxOpt( "DELETE", this.web_api_dns + url, callback ) );
    };
    
    this.postJsonAJAX = function ( url, jsonData, callback ) {
        var post_opt = this.ajaxOpt( "POST", this.web_api_dns + url, callback );
        post_opt.data = jsonData;
        post_opt.contentType = "application/json; charset=utf-8";
        this.apiSubmit( post_opt );
    };
    
    this.fileUpload = function ( url, formFile, callback ) {
        var upload_opt = this.ajaxOpt( "POST", this.web_api_dns + url, callback );
        upload_opt.data = formFile;
        upload_opt.async = false;
        upload_opt.contentType = false;
        upload_opt.enctype = 'multipart/form-data';
        upload_opt.processData = false;
        this.apiSubmit( upload_opt );
    };
    
    this.newApiToken = function ( callback ) {
        this.ajaxHolder.push( callback );
        if ( this.isTokening === true ) {
            return;
        }
        this.isTokening = true;
        $.ajax( this.ajaxOpt( "GET", "/api-token", {
            complete: function ( jqXHR, textStatus ) {
                if (jqXHR.status == 200 && jqXHR.getResponseHeader( this.auth_name ).length > 0) {
                    this.g_x_token[ this.auth_name ] = jqXHR.getResponseHeader( this.auth_name );
                    while (this.ajaxHolder.length) {
                        var _holder = this.ajaxHolder.shift();
                        _holder();
                    }
                } else {
                    alert("Your Authorization Has Problem, Please Log in Again");
                    window.location.href = "/logout";
                }
                this.isTokening = false; // open block.
            }.bind(this)
        }));
    };
    
    this.apiSubmit = function ( _opt ) {
        if ( this.auth_name in _opt.headers ) {
            $.ajax( _opt );
        } else {
            this.newApiToken( function () {
                $.ajax( _opt );
            } );
        }
    };
    
    this.datatablesOpt = function ( method, url, pl, callback ) {
        return {
            "responsive":   true,
            "ordering":     true,
            "order":        [[0, 'asc']],
            "info":         false,
            "pageLength":   pl,
            "pagingType":   "numbers",
            "lengthMenu":   [ 5, 10, 20, 50, 100 ],
            "lengthChange": true,
            "searching":    true,
            "serverSide":   true,
            "processing":   true,
            "destroy":      true,    // allow to reinitialise
            "dom":          '<"pull-left"f>ti<"pull-left"l><"pull-right"p>',
            "columns":      undefined,
            "ajax": {
                url:         this.web_api_dns + url,
                headers:     this.g_x_token,
                type:        method,
                method:      method,
                cache:       false,
                dataType:    "json",
                processData: true,
                contentType: "application/json; charset=utf-8",
                data: function (d) {
                    if (method.toUpperCase() !== "GET") {
                        return JSON.stringify({ 
                            "draw":   d.draw,
                            "page":   d.start,        // default beginning is 0.
                            "per":    d.length,
                            "search": d.search.value,
                            "order":  d.order
                        });
                    } else {
                        return { 
                            "draw":   d.draw,
                            "page":   d.start,        // default beginning is 0.
                            "per":    d.length,
                            "search": d.search.value,
                            "order":  d.order
                        };
                    }
                },
                dataSrc: function ( json ) {
                    return json.data;
                }
            },
            "drawCallback": function (settings) {
                if ( typeof callback === 'function' ) {
                    callback( settings );
                }
            }
        };
    };
    
    this.datatableSubmit = function ( tableID, _opt ) {
        if ( this.auth_name in _opt.ajax.headers ) {
            $(tableID).DataTable(_opt);
        } else {
            this.newApiToken( function () {
                $(tableID).DataTable(_opt);
            });
        }
    }
};

// ------------------------------- api functions -------------------------------
gnAjax.prototype.phoneZoneSelector = function (callback) {
    this.getAJAX( "/asset/json/phone-zone.json", callback );
};

gnAjax.prototype.countryCodeSelector = function (callback) {
    this.getAJAX( "/asset/json/country-code.json", callback );
};

gnAjax.prototype.getAccountProfile = function (callback) {
    this.getAJAX( "/account/profile", callback );
};

gnAjax.prototype.setAccountProfile = function ( name, msg, callback ) {
    if (typeof name !== "string" || name.length === 0 || typeof msg !== "string") {
        return false;
    }
    var json = JSON.stringify({
        "name": name,
        "msg": (msg.length === 0 ? '' : msg)
    });
    this.postJsonAJAX( "/account/profile/edit", json, callback );
    return true;
};

gnAjax.prototype.changeAccountPw = function (pw, ex_pw, callback) {
    if (typeof pw !== "string" || pw.length === 0) {
        return false;
    }
    var json = JSON.stringify({
        "pw": pw,
        "ex_pw": ex_pw
    });
    this.postJsonAJAX( "/account/change/pw", json, callback );
    return true;
};

gnAjax.prototype.genRole = function (name, perms, callback) {
    if ( typeof name !== "string" || name.length === 0 || typeof perms !== "object" || jQuery.isEmptyObject(perms) ) {
        return false;
    }
    var json = JSON.stringify({
        "name": name, 
        "perms": perms
    });
    this.postJsonAJAX( "/role/generate", json, callback );
    return true;
};

gnAjax.prototype.delRole = function (id, callback) {
    if (!$.isNumeric(id) || id < 0 ) {
        return false;
    }
    var json = JSON.stringify({
        "id": id
    });
    this.postJsonAJAX( "/role/delete", json, callback );
    return true;
};

gnAjax.prototype.roleList = function (tableID, pglen, hasOption, callback) {
    if (typeof tableID !== 'string' || tableID.length < 1 || !$.isNumeric(pglen) || pglen < 1) {
        return;
    }
    
    var tableOptions = this.datatablesOpt( "POST", '/role/list', pglen, callback);
    tableOptions.ajax.dataSrc = function ( json ) {
        // if you set option button, add a column value for active column.
        if (typeof hasOption === "string" && hasOption.length > 0) {
            var i;
            var ien = json.data.length;
            for (i = 0; i < ien; i++) {
                json.data[i]["active"] = 0;
            }
        }
        return json.data;
    };
    tableOptions.columns = [
        { title: "Name", data: "name", orderable: true },
        { 
            title: "State", 
            data: "editable", 
            orderable: true,
            render: function ( data, type, row ) {
                if ( type === 'display' ) {
                    var html = '';
                    switch (row.editable) {
                        case 0:
                            html = 'system';
                            break;
                        case 1:
                            html = 'customized';
                            break;
                        default:
                            html = 'unknown';
                    }
                    return html;
                }
                return data;
            }
        }
    ];
    
    if (typeof hasOption === "string" && hasOption.length > 0) {
        tableOptions.columns.push(
            {
                title:     "Option",
                data:      "active",
                orderable: false,
                className: 'text-right',
                render: function ( data, type, row ) {
                    if ( type === 'display' ) {
                        var btn = hasOption;
                        return btn;
                    }
                    return data;
                }
            }
        );
    }
    
    if (!$(tableID).is(':empty')) {
        $(tableID).DataTable().destroy(); // clear older one if it is existed.
    }
    this.datatableSubmit( tableID, tableOptions );
};

gnAjax.prototype.getRoleProperties = function (role_id, callback) {
    if (!$.isNumeric(role_id) || role_id < 1) {
        return;
    }
    this.postJsonAJAX( "/role/properties", JSON.stringify({ "id": role_id }), callback );
};

gnAjax.prototype.setRoleProperties = function (group_id, perms, callback) {
    if (!$.isNumeric(group_id) || typeof perms !== "object" || jQuery.isEmptyObject(perms) ) {
        return;
    }
    var json = JSON.stringify({
        "id":    group_id,
        "perms": perms
    });
    this.postJsonAJAX( "/role/edit", json, callback );
};

gnAjax.prototype.memberList = function (tableID, pglen, callback) {
    if (typeof tableID !== 'string' || tableID.length < 1 || !$.isNumeric(pglen) || pglen < 1) {
        return;
    }
    
    var tableOptions = this.datatablesOpt( "POST", '/member/list', pglen, callback);
    tableOptions.order = [[1, 'asc'],[0, 'asc']];
    tableOptions.ajax.dataSrc = function ( json ) {
        var i;
        var ien = json.data.length;
        for (i = 0; i < ien; i++) {
            json.data[i]["active"] = 0;
        }
        return json.data;
    };
    tableOptions.columns = [
        { title: "Email", data: "email" },
        {
            title: "Type",
            data: "type",
            render: function (data, type, row) {
                if ( type === 'display' ) {
                    var status_html = '';
                    switch (row.type) {
                        case 1:
                            status_html += '<i title="owner" class="fas fa-crown text-warning"></i>';
                            break;
                        case 2:
                            status_html += '<i title="administrator" class="fas fa-user-shield text-info"></i>';
                            break;
                        case 3:
                            status_html += '<i title="member" class="fas fa-user text-muted"></i>';
                            break;
                    }
                    return status_html;
                }
                return data;
            }
        },
        { title: "Name", data: "name" },
        { title: "Role", data: "role" },
        {
            title: "Status",
            data: "status",
            render: function (data, type, row) {
                if ( type === 'display' ) {
                    var status_html = '';
                    switch (row.status) {
                        case 1:
                            status_html = '<i title="activate" class="fas fa-check-circle text-success"></i>';
                            break;
                        case 2:
                            status_html = '<i title="block" class="fas fa-ban text-danger"></i>';
                            break;
                        case 3:
                            status_html = '<i title="initial" class="fas fa-envelope-open-text text-warning"></i>';
                    }
                    return status_html;
                }
                return data;
            }
        },
        {
            orderable: false,
            title: "",
            data: "active",
            render: function ( data, type, row ) {
                if ( type === 'display' ) {
                    var box = '<button type="button" class="option-btn text-secondary btn btn-link btn-sm"><i class="fas fa-chevron-right"></i></button';
                    return box;
                }
                return data;
            },
            className: "text-right"
        }
    ];
    
    if (!$(tableID).is(':empty')) {
        $(tableID).DataTable().destroy(); // clear older one if it is existed.
    }
    this.datatableSubmit( tableID, tableOptions );
};

gnAjax.prototype.inviteMember = function (email, type, group, callback) {
    if (typeof email !== "string" || email.length == 0 || !$.isNumeric( type ) || !$.isNumeric( group ) )
    {
        return false;
    }
    var json = JSON.stringify({
        "email": email,
        "type":  parseInt(type),
        "role": parseInt(group) // must in order for the permission flags
    });
    this.postJsonAJAX( "/member/invite", json, callback );
    return true;
};

gnAjax.prototype.reinviteMember = function (email, callback) {
    if (typeof email !== "string" || email.length == 0 ){
        return false;
    }
    var json = JSON.stringify({
        "email": email
    });
    this.postJsonAJAX( "/member/re-invite", json, callback );
    return true;
};

gnAjax.prototype.getMemberInfo = function (id, callback) {
    if ( !$.isNumeric(id) || id <= 0 ) {
        return false;
    }
    var json = JSON.stringify({
        "id": id
    });
    this.postJsonAJAX( "/member/view", json, callback );
    return true;
};

gnAjax.prototype.setMemberState = function (id, type, role, status, callback) {
    if (!$.isNumeric(id) || !$.isNumeric(type) || !$.isNumeric(role) || !$.isNumeric(status)) {
        return false;
    }
    var json = JSON.stringify({
        "id":     id,
        "type":   type,
        "role":   role,
        "status": status
    });
    this.postJsonAJAX( "/member/edit/state", json, callback );
    return true;
};
