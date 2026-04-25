import { taskbar } from "/Application/Desktop/Module/Taskbar.js";
import { getSectionById } from "/Module/Section.js";
import { dialog } from "/Dialog/Module/Dialog.js";
import { file } from "/Application/Filemanager/Module/File.js";
import user from "/Module/User.js";

let player = {};

player.init = (id) => {
    taskbar.add('application-video-player', id);

    player.menu(id);
    player.menu_application(id);
    player.player(id);
    player.close(id);
}

player.close = (id) => {
    let section = getSectionById(id);
    if(!section){
        return;
    }
    let close = section.select('.close');
    close.on('click', (event) => {
        taskbar.delete(section.attribute('id'));
    });
}

player.menu_application = (id) => {
    const section = getSectionById(id);
    if(!section){
        return;
    }
    dialog.click(section, '.menu-application-video-player');

}

player.menu = (id) => {
    const section = getSectionById(id);
    if(!section){
        return;
    }
    const menu = section.select('.menu');
    if(!menu){
        return;
    }
    const menu_file = menu.select('li.file');
    const menu_file_menu = menu.select('.menu-file');
    const menu_file_protector = menu.select('.menu-file-protector');
    if(menu_file){
        menu_file.on('click', (event) => {
            if(menu_file_menu) {
                menu_file_menu.toggleClass('display-none');
            }
            if(menu_file_protector){
                menu_file_protector.toggleClass('display-none');
            }
        });
    }
    if(menu_file_protector){
        menu_file_protector.on('click', (event) => {
            if(menu_file_menu){
                menu_file_menu.addClass('display-none');
                menu_file_protector.addClass('display-none');
            }
        });
    }
    const menu_file_exit = menu.select('.menu-file-exit');
    if(menu_file_exit){
        menu_file_exit.on('click', (event) => {
            taskbar.delete(section.attribute('id'));
            section.remove();
        });
    }
    const menu_file_open = menu.select('.menu-file-open');
    if(menu_file_open){
        menu_file_open.on('click', (event) => {
            if(menu_file_protector){
                menu_file_protector.trigger('click');
            }
            console.log('need application file manager open url');
            console.log(file);
            /*
            const file_manager_section = getSection(file.data.get('section.id'));
            if(!file_manager_section){
                return;
            }*/
            /*
            const input = file_manager_section.select('input[name="address"]');
            if(!input){
                return;
            }
             */
        });
    }
    dialog.click(section, '.menu');
}

player.player = (id) => {
    const section = getSectionById(id);
    if(!section){
        return;
    }
    let key = user.get('key');
    let src = section.select('input[name="node.source"]')?.value;
    let type = section.select('input[name="node.type"]')?.value;
    if(key){
        src += '&key=' + key;
    }
    let video = _('_').create('video');
    video.crossOrigin='anonymous';
    video.autoplay=true
    video.controls=true;
    let source = _('_').create('source');
    source.src = src;
    source.type = type;
    video.appendChild(source);


    console.log(source.src);
    const body = section.select('.body');
    body.appendChild(video);
    video = _('_').create('video');
    video.crossOrigin='anonymous';
    video.autoplay=true
    video.controls=true;
    let split = source.src.split('.webm');
    let audio = _('_').create('audio');
    audio.crossOrigin='anonymous';
    audio.autoplay=true
    audio.controls=true;
    let source_audio = _('_').create('source');
    if(split.length > 1){
        source_audio.src = source.src.split('.webm').join('.ogg');
        source_audio.type = 'audio/ogg';
    } else {
        source_audio.src = source.src.split('.mp4').join('.mp3');
        source_audio.type = 'audio/mp3';
    }
    audio.appendChild(source_audio);
    body.appendChild(video);
    body.appendChild(audio);
}

export { player }