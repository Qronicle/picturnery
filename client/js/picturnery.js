var Picturnery = {

    MESSAGE_TYPE_UNKNOWN: 'unknown',
    MESSAGE_TYPE_NEW_USER: 'roomNewUser',
    MESSAGE_TYPE_USER_LEFT: 'roomUserLeft',
    MESSAGE_TYPE_USER_LIST: 'roomUserList',
    MESSAGE_TYPE_PICTURNERY_GUESS: 'picturneryGuess',
    MESSAGE_TYPE_PICTURNERY_DRAWING_UPDATE: 'picturneryDrawingUpdate',
    MESSAGE_TYPE_PICTURNERY_NEW_GAME: 'picturneryNewGame',
    MESSAGE_TYPE_PICTURNERY_END_GAME: 'picturneryEndGame',
    MESSAGE_TYPE_PICTURNERY_NEW_ROUND: 'picturneryNewRound',
    MESSAGE_TYPE_PICTURNERY_END_ROUND: 'picturneryEndRound',

    REQUEST_TYPE_USER_LIST: 'roomUserList',
    REQUEST_TYPE_PICTURNERY_GUESS: 'picturneryGuess',
    REQUEST_TYPE_PICTURNERY_DRAWING_UPDATE: 'picturneryDrawingUpdate',

    $guessList: null,
    guessTemplate: '',

    $userList: null,
    userTemplate: '',

    $roundStatus: null,
    waitingTemplate: '',
    guessCountdownTemplate: '',
    drawCountdownTemplate: '',
    roundFinishedTemplate: '',
    roundFinishedNoGuessersTemplate: '',

    socket: null,

    user: null,
    room: null,

    init: function (user, room) {
        this.user = user;
        this.room = room;

        //create a new WebSocket object.
        var wsUri = "ws://192.168.93.128:1337/server.php?user=" + encodeURIComponent(this.user.id) + "&hash=" + encodeURIComponent(user.hash) + "&room=" + encodeURIComponent(room.id);
        this.socket = new WebSocket(wsUri);

        this.socket.onopen = function (ev) { // connection is open
            console.log('Connected - awaiting welcome message');
        };

        this.socket.onmessage = Picturnery.onMessage;

        this.socket.onerror = function (ev) {
            console.log("Error Occurred - " + ev.data);
        };
        this.socket.onclose = function (ev) {
            console.log('Connection closed');
        };

        this.$guessList = $('#guess-list');
        this.$userList = $('#user-list');
        this.$roundStatus = $('#round-information');

        this.guessTemplate = $('#guess-template').html();
        this.userTemplate = $('#user-template').html();
        this.waitingTemplate = $('#round-information-waiting-for-players').html();
        this.guessCountdownTemplate = $('#round-information-guess-countdown').html();
        this.drawCountdownTemplate = $('#round-information-draw-countdown').html();
        this.roundFinishedTemplate = $('#round-information-round-finished').html();
        this.roundFinishedNoGuessersTemplate = $('#round-information-round-finished-no-guessers').html();

        $('#guess').on('keyup', function (e) {
            if (e.keyCode == 13 && $.trim($(this).val()) != '') {
                Picturnery.guess($(this).val());
                $(this).val('');
            }
        });

        Drawing.init($('#drawing'));
        Drawing.clear();
        Drawing.mode = Drawing.MODE_VIEW;
        //Drawing.draw([[{"x":203,"y":203},{"x":200,"y":204},{"x":199,"y":207},{"x":198,"y":210},{"x":198,"y":213},{"x":198,"y":217},{"x":198,"y":220},{"x":201,"y":223},{"x":206,"y":227},{"x":209,"y":230},{"x":212,"y":230},{"x":216,"y":231},{"x":219,"y":231},{"x":223,"y":231},{"x":227,"y":231},{"x":230,"y":231},{"x":232,"y":227},{"x":234,"y":224},{"x":234,"y":221},{"x":235,"y":217},{"x":235,"y":214},{"x":235,"y":210},{"x":233,"y":207},{"x":230,"y":205},{"x":227,"y":204},{"x":224,"y":203},{"x":221,"y":203},{"x":217,"y":203},{"x":214,"y":203},{"x":211,"y":203}],[{"x":315,"y":200},{"x":312,"y":200},{"x":309,"y":200},{"x":306,"y":203},{"x":303,"y":206},{"x":301,"y":209},{"x":300,"y":212},{"x":300,"y":215},{"x":301,"y":219},{"x":303,"y":222},{"x":306,"y":225},{"x":309,"y":226},{"x":312,"y":226},{"x":315,"y":226},{"x":318,"y":225},{"x":322,"y":223},{"x":325,"y":220},{"x":326,"y":216},{"x":326,"y":213},{"x":326,"y":210},{"x":325,"y":206},{"x":322,"y":204},{"x":320,"y":201},{"x":317,"y":201}],[{"x":222,"y":218}],[{"x":311,"y":214}],[{"x":258,"y":246}],[{"x":274,"y":246}],[{"x":264,"y":259},{"x":261,"y":259},{"x":258,"y":260},{"x":255,"y":261},{"x":252,"y":263},{"x":249,"y":264},{"x":246,"y":266},{"x":244,"y":269},{"x":243,"y":272},{"x":243,"y":275},{"x":244,"y":279},{"x":247,"y":282},{"x":249,"y":285},{"x":252,"y":286},{"x":256,"y":288},{"x":261,"y":290},{"x":265,"y":291},{"x":269,"y":291},{"x":272,"y":291},{"x":275,"y":291},{"x":276,"y":288},{"x":278,"y":285},{"x":278,"y":282},{"x":278,"y":279},{"x":279,"y":276},{"x":279,"y":273},{"x":279,"y":270},{"x":278,"y":267},{"x":275,"y":264},{"x":272,"y":262},{"x":269,"y":261},{"x":266,"y":260}],[{"x":290,"y":306},{"x":293,"y":305},{"x":296,"y":303},{"x":299,"y":302},{"x":302,"y":298},{"x":304,"y":295},{"x":305,"y":292},{"x":307,"y":287},{"x":308,"y":284},{"x":308,"y":281},{"x":308,"y":277},{"x":308,"y":274},{"x":307,"y":270},{"x":306,"y":267},{"x":305,"y":264},{"x":308,"y":263},{"x":311,"y":263},{"x":316,"y":263},{"x":319,"y":262},{"x":324,"y":260},{"x":327,"y":259},{"x":332,"y":258},{"x":335,"y":256},{"x":338,"y":254},{"x":341,"y":253},{"x":345,"y":250},{"x":348,"y":248},{"x":351,"y":246},{"x":354,"y":242},{"x":359,"y":237},{"x":361,"y":231},{"x":363,"y":226},{"x":364,"y":221},{"x":364,"y":218},{"x":364,"y":213},{"x":364,"y":210},{"x":364,"y":206},{"x":364,"y":203},{"x":364,"y":200},{"x":363,"y":197},{"x":362,"y":193},{"x":360,"y":190},{"x":357,"y":187},{"x":354,"y":184},{"x":351,"y":181},{"x":347,"y":178},{"x":343,"y":175},{"x":338,"y":172},{"x":335,"y":171},{"x":332,"y":168},{"x":329,"y":167},{"x":325,"y":165},{"x":321,"y":164},{"x":318,"y":163},{"x":313,"y":161},{"x":309,"y":161},{"x":302,"y":159},{"x":299,"y":158},{"x":296,"y":158},{"x":292,"y":157},{"x":287,"y":157},{"x":284,"y":157},{"x":278,"y":156},{"x":275,"y":156},{"x":272,"y":156},{"x":267,"y":156},{"x":264,"y":156},{"x":261,"y":156},{"x":258,"y":156},{"x":253,"y":156},{"x":250,"y":156},{"x":246,"y":156},{"x":240,"y":156},{"x":237,"y":158},{"x":231,"y":160},{"x":228,"y":160},{"x":225,"y":160},{"x":221,"y":162},{"x":218,"y":163},{"x":214,"y":165},{"x":211,"y":166},{"x":208,"y":167},{"x":203,"y":170},{"x":199,"y":173},{"x":195,"y":175},{"x":191,"y":178},{"x":187,"y":181},{"x":184,"y":185},{"x":180,"y":189},{"x":177,"y":193},{"x":174,"y":198},{"x":171,"y":201},{"x":169,"y":204},{"x":168,"y":207},{"x":166,"y":212},{"x":166,"y":215},{"x":166,"y":218},{"x":166,"y":221},{"x":166,"y":225},{"x":166,"y":229},{"x":168,"y":232},{"x":169,"y":235},{"x":172,"y":239},{"x":175,"y":243},{"x":178,"y":245},{"x":180,"y":248},{"x":183,"y":250},{"x":187,"y":252},{"x":190,"y":254},{"x":193,"y":254},{"x":197,"y":255},{"x":200,"y":257},{"x":204,"y":257},{"x":208,"y":257},{"x":212,"y":257},{"x":215,"y":257},{"x":218,"y":257},{"x":218,"y":261},{"x":217,"y":265},{"x":215,"y":268},{"x":213,"y":273},{"x":212,"y":276},{"x":212,"y":280},{"x":212,"y":284},{"x":214,"y":287},{"x":214,"y":290},{"x":216,"y":293},{"x":219,"y":297},{"x":222,"y":301},{"x":226,"y":306},{"x":229,"y":308},{"x":232,"y":311},{"x":235,"y":314},{"x":240,"y":316},{"x":244,"y":319},{"x":247,"y":320},{"x":251,"y":321},{"x":254,"y":322},{"x":257,"y":323},{"x":260,"y":323},{"x":263,"y":323},{"x":266,"y":322},{"x":269,"y":321},{"x":272,"y":320},{"x":275,"y":319},{"x":278,"y":319},{"x":281,"y":317},{"x":282,"y":314},{"x":283,"y":311},{"x":285,"y":308}],[{"x":252,"y":269},{"x":250,"y":272},{"x":254,"y":272},{"x":256,"y":269},{"x":258,"y":272},{"x":258,"y":275},{"x":258,"y":278},{"x":258,"y":281},{"x":260,"y":275},{"x":262,"y":272},{"x":265,"y":268},{"x":266,"y":271},{"x":266,"y":276},{"x":266,"y":279},{"x":266,"y":282},{"x":266,"y":285},{"x":269,"y":285},{"x":272,"y":281},{"x":274,"y":278},{"x":275,"y":283},{"x":276,"y":286},{"x":271,"y":286},{"x":267,"y":284},{"x":264,"y":282},{"x":261,"y":280},{"x":258,"y":280},{"x":255,"y":280},{"x":254,"y":277},{"x":254,"y":274},{"x":254,"y":270},{"x":256,"y":266},{"x":259,"y":264},{"x":263,"y":262},{"x":266,"y":262},{"x":266,"y":266},{"x":263,"y":269},{"x":260,"y":271},{"x":257,"y":271},{"x":254,"y":271},{"x":251,"y":273},{"x":251,"y":276},{"x":251,"y":279},{"x":254,"y":280},{"x":257,"y":280},{"x":260,"y":281},{"x":264,"y":281},{"x":267,"y":283},{"x":270,"y":284},{"x":273,"y":284},{"x":276,"y":282},{"x":276,"y":278},{"x":276,"y":275},{"x":272,"y":273},{"x":269,"y":272},{"x":266,"y":272},{"x":263,"y":271}]]);
        //Drawing.mode = Drawing.MODE_IDLE;
        Drawing.$canvas.on('update', function (e, drawing, update) {
            Picturnery.socket.send(JSON.stringify({
                type: Picturnery.REQUEST_TYPE_PICTURNERY_DRAWING_UPDATE,
                user: Picturnery.user,
                room: Picturnery.room,
                update: update
            }));
        });

    },

    guess: function (guess) {
        Picturnery.socket.send(JSON.stringify({
            type: Picturnery.REQUEST_TYPE_PICTURNERY_GUESS,
            user: Picturnery.user,
            room: Picturnery.room,
            guess: guess
        }));
    },

    refreshUserList: function () {
        Picturnery.socket.send(JSON.stringify({
            type: Picturnery.REQUEST_TYPE_USER_LIST,
            user: Picturnery.user,
            room: Picturnery.room
        }));
    },

    onMessage: function (e) {
        var message = JSON.parse(e.data);

        switch (message.type) {
            case Picturnery.MESSAGE_TYPE_NEW_USER:
                // Add user to list
                var userHtml = Picturnery.parseTemplate(Picturnery.userTemplate, {
                    id: message.user.id,
                    username: message.user.username,
                    score: message.user.game.score
                });
                Picturnery.$userList.append(userHtml);
                break;
            case Picturnery.MESSAGE_TYPE_USER_LEFT:
                Picturnery.$userList.find('#user-' + message.user.id).remove();
                break;
            case Picturnery.MESSAGE_TYPE_USER_LIST:
                // Refresh user list
                userHtml = '';
                for (var u in message.users) {
                    var user = message.users[u];
                    userHtml += Picturnery.parseTemplate(Picturnery.userTemplate, {
                        id: user.id,
                        username: user.username,
                        score: user.game.score
                    });
                }
                if (message.game) {
                    if (message.game.status == 'game') {
                        Picturnery.$roundStatus.html('');
                    } else {
                        Picturnery.$roundStatus.html(Picturnery.waitingTemplate);
                    }
                }
                Picturnery.$userList.html(userHtml);
                break;
            case Picturnery.MESSAGE_TYPE_PICTURNERY_GUESS:
                // Add guess to list
                var guessHtml = Picturnery.parseTemplate(Picturnery.guessTemplate, {
                    username: message.user.username,
                    guess: message.guess
                });
                Picturnery.$guessList.append(guessHtml);
                break;
            case Picturnery.MESSAGE_TYPE_PICTURNERY_DRAWING_UPDATE:
                console.log('update drawing');
                Drawing.draw([message.update.data]);
                break;
            case Picturnery.MESSAGE_TYPE_PICTURNERY_NEW_ROUND:
                Drawing.clear();
                Drawing.mode = Drawing.MODE_VIEW;
                switch (message.role) {
                    case 'draw':
                        var template = Picturnery.drawCountdownTemplate;
                        var variables = {
                            word: message.word,
                            roundDuration: message.roundDuration
                        };
                        Drawing.mode = Drawing.MODE_IDLE;
                        break;
                    default:
                        template = Picturnery.guessCountdownTemplate;
                        variables = {
                            username: message.drawer.username,
                            roundDuration: message.roundDuration
                        };
                }
                Picturnery.$roundStatus.html(Picturnery.parseTemplate(template, variables));
                break;
            case Picturnery.MESSAGE_TYPE_PICTURNERY_END_ROUND:
                Drawing.mode = Drawing.MODE_VIEW;
                variables = {word: message.word};
                if (message.guessers.length) {
                    var guesserString = [];
                    for (u in message.guessers) {
                        guesserString.push(message.guessers[u].username);
                    }
                    guesserString = '<b>' + guesserString.join('</b>, <b>') + '</b>';
                    variables.guessers = guesserString;
                    Picturnery.$roundStatus.html(Picturnery.parseTemplate(Picturnery.roundFinishedTemplate, variables));
                } else {
                    Picturnery.$roundStatus.html(Picturnery.parseTemplate(Picturnery.roundFinishedNoGuessersTemplate, variables));
                }
                variables = {
                    word: message.word,
                    guessers: guesserString
                };

                break;
        }
    },

    parseTemplate: function (template, variables) {
        for (var key in variables) {
            var value = variables[key];
            template = template.split('##' + key + '##').join(value);
        }
        return template;
    }
};