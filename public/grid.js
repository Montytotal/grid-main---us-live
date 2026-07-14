const LABELS = {
  price       : 'Price per MWh',
  emissions   : 'Emissions per kWh',
  demand      : 'Demand',
  generation  : 'Generation',
  fossils     : 'Fossil fuels',
  renewables  : 'Renewables',
  others      : 'Other sources',
  transfers   : 'Net cross-border flow',
  coal        : 'Coal',
  gas         : 'Gas',
  solar       : 'Solar',
  wind        : 'Wind',
  hydro       : 'Hydroelectric',
  hydroelectric : 'Hydroelectric',
  nuclear     : 'Nuclear',
  biomass     : 'Biomass',
  oil         : 'Oil',
  canada      : 'Canada',
  mexico      : 'Mexico',
  belgium     : 'Belgium',
  denmark     : 'Denmark',
  france      : 'France',
  ireland     : 'Ireland',
  netherlands : 'Netherlands',
  norway      : 'Norway',
  pumped      : 'Pumped storage',
}

const KEY_MARGIN = 8
const REFRESH_INTERVAL = 30 * 60 * 1000
const REFRESH_OFFSET = 2 * 60 * 1000

const IDS_TO_UPDATE = [
  'status',
  'latest',
  'tab-panel-day',
  'tab-panel-week',
  'tab-panel-year',
  'tab-panel-all',
  'us-summary',
  'transition'
]

let key    = document.createElement('div')
let dialog = document.querySelector('dialog')
let delay  = Math.random() * 60000
let parser = new DOMParser()

document.addEventListener('visibilitychange', update)
document.body.addEventListener('click', handleClick)

let tabList = document.querySelector('[role="tablist"]')

if (tabList) {
  let initialTab = getTabFromHash(tabList)
    || tabList.querySelector('[aria-selected="true"]')
    || tabList.firstElementChild

  selectTab(tabList, initialTab)
  tabList.addEventListener('click', handleTabClick)
  tabList.addEventListener('keydown', handleTabKeyDown)
  window.addEventListener('hashchange', handleTabHashChange)
}

addGraphListeners()
scheduleUpdate()

// Adds the listeners to the graphs
function addGraphListeners() {
  document.querySelectorAll('.pie-chart').forEach(pieChart => {
    pieChart.addEventListener('mouseover', e => updatePieChartKey(e, true))
    pieChart.addEventListener('mouseout',  e => updatePieChartKey(e, false))
  })

  document.querySelectorAll('.graph svg').forEach(graph => {
    graph.addEventListener('mouseover', showGraphKey)
    graph.addEventListener('mouseleave', hideGraphKey)
  })
}

// Handles a click by showing a help dialog if appropriate
function handleClick(e) {
  let help = e.target.dataset.help

  if (help && dialog) {
    dialog.children[0].innerHTML = e.target.parentNode.textContent
    dialog.children[2].innerHTML = help
    dialog.showModal()
  }
}

// Updates a pie chart key
function updatePieChartKey(e, showDetails) {
  if (e.target.nodeName === 'path') {
    let sourceNode = e.target.parentNode
    let source     = 'generation'
    let pieChart   = sourceNode.parentNode

    if (showDetails) {
      sourceNode = e.target
      source     = e.target.getAttribute('class')
    }

    let nodes = pieChart.querySelectorAll('div,span')

    nodes[1].textContent = LABELS[source] || source
    nodes[2].className   = source
    nodes[4].textContent = sourceNode.dataset.power
    nodes[6].textContent = sourceNode.dataset.percentage
  }
}

// Selects a tab
function selectTab(tabList, tab, updateHash = false) {
  if (!tabList || !tab) {
    return
  }

  let panels = Array.from(
    tabList.parentNode.querySelectorAll('[role="tabpanel"]')
  )

  for (let node of tabList.children) {
    let selected = (node === tab)

    node.setAttribute('aria-selected', (selected ? 'true' : 'false'))
    node.tabIndex = (selected ? 0 : -1)

    let panel = panels.shift()

    if (panel) {
      panel.style.display = (selected ? 'grid' : 'none')
    }
  }

  if (updateHash && tab.dataset.period) {
    window.history.replaceState(null, '', '#' + tab.dataset.period)
  }
}

// Finds a range tab from a shareable URL fragment.
function getTabFromHash(tabList) {
  let period = window.location.hash.substring(1)

  for (let tab of tabList.children) {
    if (tab.dataset.period === period) {
      return tab
    }
  }

  return null
}

// Updates the selected range when a shared period URL is opened.
function handleTabHashChange() {
  let tab = getTabFromHash(tabList)

  if (tab) {
    selectTab(tabList, tab)
  }
}

// Handles a click on a tab
function handleTabClick(e) {
  if (e.target.parentNode === this) {
    selectTab(this, e.target, true)
  } else if (e.target.parentNode.parentNode === this) {
    selectTab(this, e.target.parentNode, true)
  }
}

// Handles a key down on a tab
function handleTabKeyDown(e) {
  let tabs  = Array.from(this.children)
  let count = tabs.length
  let index = tabs.indexOf(this.querySelector('[aria-selected="true"]'))

  let preventDefault = true

  switch (e.key) {
    case 'ArrowLeft':  index = (index + count - 1) % count; break
    case 'ArrowRight': index = (index + 1) % count;         break
    case 'Home':       index = 0;                           break
    case 'End':        index = count - 1;                   break
    default:           preventDefault = false
  }

  if (preventDefault) {
    e.preventDefault()
  }

  selectTab(this, tabs[index], true)
  tabs[index].focus()
}

// Shows the graph key
function showGraphKey(e) {
  if (e.target.nodeName !== 'rect') {
    return
  }

  let graph     = e.target.parentNode.parentNode
  let transfers = graph.dataset.transfers === 'true'
  let prefix    = graph.dataset.prefix
  let suffix    = graph.dataset.suffix
  let classes   = Array.from(graph.querySelectorAll('polyline')).map(
    series => series.className.baseVal
  )
  let values    = e.target.dataset.values.split(' ')

  let time = document.createElement('div')
  time.append(e.target.dataset.time)

  let table = document.createElement('table')
  table.className = 'sources' + (transfers ? ' transfers' : '')

  let body  = table.createTBody()

  for (let i = 0; i < values.length; i ++) {
    let isNegative = values[i].substring(0,1) === '-'

    let row = body.insertRow()
    row.insertCell().className = classes[i]
    row.insertCell().textContent = LABELS[classes[i]]
    row.insertCell().textContent = (
      (isNegative ? '\u2212' : '')
      + prefix
      + values[i].substring(isNegative ? 1 : 0)
      + suffix
    )
  }

  key.innerHTML = ''
  key.append(time, table)
  graph.append(key)

  let keyWidth        = key.offsetWidth
  let overlayPosition = e.target.getBoundingClientRect()

  let left = overlayPosition.left - graph.getBoundingClientRect().left

  if (overlayPosition.left > keyWidth + 2 * KEY_MARGIN) {
    left -= keyWidth + KEY_MARGIN
  } else {
    left += overlayPosition.width + KEY_MARGIN
  }

  key.style.left = left + 'px'
}

// Hides the graph key
function hideGraphKey() {
  key.remove()
}

// Schedules an update after each 30-minute site build, with an offset of two
// minutes plus a visitor-specific random delay of up to a minute to reduce
// simultaneous requests.
function scheduleUpdate() {
  setTimeout(
    update,
    (
      REFRESH_INTERVAL
      + REFRESH_OFFSET
      - (Date.now() % REFRESH_INTERVAL)
      + delay
    ) % REFRESH_INTERVAL
  )
}

// Updates the user interface. The 'unscheduled' flag is used for updates
// triggered when the page becomes visible again after updates were suspended
// while the page was not visible.
function update(unscheduled) {
  let time = Math.floor(Date.now() / REFRESH_INTERVAL)

  if (
    unscheduled
    && (Date.now() % REFRESH_INTERVAL) < (REFRESH_OFFSET + delay)
  ) {
    time --
  }

  if (document.visibilityState === 'visible') {
    fetch('?v=' + time).then(response => response.text()).then(html => {
      let update = parser.parseFromString(html, 'text/html')

      IDS_TO_UPDATE.forEach(id => {
        let currentNode = document.getElementById(id)
        let updatedNode = update.getElementById(id)

        if (currentNode && updatedNode) {
          currentNode.replaceChildren(...updatedNode.children)
        }
      })

      hideGraphKey()

      addGraphListeners()
    })
  }

  if (!unscheduled) {
    scheduleUpdate()
  }
}
