const test = require('node:test');
const assert = require('node:assert/strict');
const { parseStandingScore, calculateStandings } = require('../slt-final/standings.js');
const players = ['a', 'b', 'c'].map(id => ({ id, name: id, active: true }));
const match = (score, extra = {}) => ({player1Id:'a', player2Id:'b', league:'A', status:'completed', score, ...extra});
test('complete scores, tiebreaks and reversed winner', () => {
 assert.equal(parseStandingScore('6:4, 6:3').wins1, 2);
 assert.equal(parseStandingScore('6:7(5), 7:5, 4:6').wins2, 2);
 for (const score of ['', '6:4', '6:6, 6:3', '6:4, 6:3, 6:2', 'wo', '0:0, 0:0']) assert.equal(parseStandingScore(score), null);
});
test('points, sets, games, league filters and shared places', () => {
 const result = calculateStandings(players, [match('4:6, 3:6')]);
 assert.equal(result.ranking[0].id, 'b'); assert.equal(result.ranking[0].points, 3);
 assert.equal(result.ranking[0].gamesFor, 12); assert.equal(result.ranking[0].gamesAgainst, 7);
 assert.equal(result.ranking[0].setsFor, 2); assert.equal(result.ranking.find(p=>p.id==='a').lost, 1);
 assert.equal(calculateStandings(players, [match('6:4, 6:3')], 'B').ranking.length, 0);
 const zero = calculateStandings(players, [match(null,{status:'scheduled'})]);
 assert.deepEqual(zero.ranking.map(p=>p.place),[1,1,1]); assert.equal(zero.counted,0);
 assert.equal(calculateStandings(players,[match(null)],'A').omitted,1);
});
test('scheduled matches and unknown players never affect points', () => {
 const result = calculateStandings(players,[match('6:4, 6:3',{status:'scheduled'}),match('6:4, 6:3',{player2Id:'missing'})]);
 assert.equal(result.counted,0);assert.equal(result.omitted,1);assert.ok(result.ranking.every(p=>p.points===0));
});
